<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitzf5;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\web\View;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\drivers\purgers\BaseCachePurger;
use putyourlightson\blitz\events\RefreshCacheEvent;
use putyourlightson\blitz\models\SiteUriModel;
use yii\base\Event;
use yii\log\Logger;

/**
 * @property-read null|string $settingsHtml
 */
class F5Purger extends BaseCachePurger
{
    /**
     * @const int The timeout for API requests in seconds.
     */
    public const API_REQUEST_TIMEOUT = 10;

    /**
     * @var string
     */
    public string $baseUrl = '';

    /**
     * @var string
     */
    public string $namespace = '';

    /**
     * @var string
     */
    public string $name = '';

    /**
     * Whether to remove the content from the distribution, forcing the next request to retrieve the content from the origin server. With this off, the content will be replaced on the next request if the content is stale.
     * https://docs.cloud.f5.com/docs-v2/content-delivery-network/how-to/configure-cdn-distribution
     */
    public bool $hardPurge = true;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('blitz', 'F5 Purger');
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['blitz-f5'] = __DIR__ . '/templates/';
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'baseUrl',
                'namespace',
                'name',
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'baseUrl' => Craft::t('blitz-f5', 'Base URL'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['baseUrl', 'namespace', 'name'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function purgeUrisWithProgress(array $siteUris, callable $setProgressHandler = null): void
    {
        $count = 0;
        $total = count($siteUris);
        $label = 'Purging {total} pages.';

        if (is_callable($setProgressHandler)) {
            $progressLabel = Craft::t('blitz', $label, ['total' => $total]);
            call_user_func($setProgressHandler, $count, $total, $progressLabel);
        }

        $pattern = $this->getCondensedUriPattern($siteUris);
        $this->sendRequest($pattern);

        $count = $total;

        if (is_callable($setProgressHandler)) {
            $progressLabel = Craft::t('blitz', $label, ['total' => $total]);
            call_user_func($setProgressHandler, $count, $total, $progressLabel);
        }
    }

    /**
     * @inheritdoc
     */
    public function purgeAll(callable $setProgressHandler = null, bool $queue = true): void
    {
        $event = new RefreshCacheEvent();
        $this->trigger(self::EVENT_BEFORE_PURGE_ALL_CACHE, $event);

        if (!$event->isValid) {
            return;
        }

        $this->sendRequest('*');

        if ($this->hasEventHandlers(self::EVENT_AFTER_PURGE_ALL_CACHE)) {
            $this->trigger(self::EVENT_AFTER_PURGE_ALL_CACHE, $event);
        }
    }

    /**
     * @inheritdoc
     */
    public function test(): bool
    {
        return $this->sendRequest('test-purge');
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('blitz-f5/settings', [
            'purger' => $this,
        ]);
    }

    /**
     * Returns a condensed URI pattern by eagerly adding a wildcard character.
     *
     * This method returns a single URI with a wildcard character after the longest common prefix.
     * For example, if the URIs are:
     * - /foo/bar
     * - /foo/qux/baz
     *
     * The method will return `/foo/*`.
     *
     * @param SiteUriModel[] $siteUris
     */
    public function getCondensedUriPattern(array $siteUris): string
    {
        $uris = array_map(fn($siteUri) => $siteUri->uri, $siteUris);

        if (count($uris) < 2) {
            return $uris[0];
        }

        // Get the longest common prefix between the two most dissimilar strings.
        sort($uris);

        return $this->getLongestCommonPrefix(reset($uris), end($uris)) . '*';
    }

    /**
     * Returns the longest common prefix between two strings.
     */
    private function getLongestCommonPrefix($str1, $str2): string
    {
        $length = min(strlen($str1), strlen($str2));
        for ($i = 0; $i < $length; $i++) {
            if ($str1[$i] !== $str2[$i]) {
                return substr($str1, 0, $i);
            }
        }

        return substr($str1, 0, $length);
    }

    /**
     * Sends a purge request to the API.
     * https://docs.cloud.f5.com/docs-v2/api/views-cdn-loadbalancer#operation/ves.io.schema.views.cdn_loadbalancer.CustomAPI.CDNCachePurge
     */
    private function sendRequest(string $pattern): bool
    {
        $client = Craft::createGuzzleClient([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => self::API_REQUEST_TIMEOUT,
        ]);

        $baseUrl = rtrim('/', $this->baseUrl) . '/';
        $url = $baseUrl . 'api/cdn/namespaces/' . App::parseEnv($this->namespace) . '/cdn_loadbalancer/' . App::parseEnv($this->name) . '/cache-purge';

        $options = [
            'json' => [
                'pattern' => $pattern,
            ],
        ];

        /**
         * TODO: verify that this is the correct way to handle hard/soft purges, which the API docs aren’t clear on.
         * https://docs.cloud.f5.com/docs-v2/api/views-cdn-loadbalancer#operation/ves.io.schema.views.cdn_loadbalancer.CustomAPI.CDNCachePurge
         */
        if ($this->hardPurge) {
            $options['json']['hard'] = [];
        } else {
            $options['json']['soft'] = [];
        }

        try {
            $client->request('POST', $url, $options);
        } catch (BadResponseException|GuzzleException $exception) {
            Blitz::$plugin->log($exception->getMessage(), [], Logger::LEVEL_ERROR);

            return false;
        }

        return true;
    }
}
