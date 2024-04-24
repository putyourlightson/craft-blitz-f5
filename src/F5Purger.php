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
use Psr\Http\Message\ResponseInterface;
use putyourlightson\blitz\drivers\purgers\BaseCachePurger;
use putyourlightson\blitz\events\RefreshCacheEvent;
use putyourlightson\blitz\models\SiteUriModel;
use yii\base\Event;

/**
 * @property-read null|string $settingsHtml
 */
class F5Purger extends BaseCachePurger
{
    public const API_ENDPOINT = 'https://docs.cloud.f5.com/api/cdn/namespaces/';

    /**
     * @var string
     */
    public string $namespace = '';

    /**
     * @var string
     */
    public string $name = '';

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
                'namespace',
                'name',
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['namespace', 'name'], 'required'],
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
        $this->sendRequest($this->getPurgeUri(), $this->getPurgeParams($pattern));

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

        $this->sendRequest($this->getPurgeUri(), $this->getPurgeParams('*'));

        if ($this->hasEventHandlers(self::EVENT_AFTER_PURGE_ALL_CACHE)) {
            $this->trigger(self::EVENT_AFTER_PURGE_ALL_CACHE, $event);
        }
    }

    /**
     * @inheritdoc
     */
    public function test(): bool
    {
        $response = $this->sendRequest('list-service-operations-status');

        if (!$response) {
            return false;
        }

        return $response->getStatusCode() == 200;
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
     * This method returns a single URL with a wildcard character after the longest common prefix.
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
     * Returns the purge URI.
     */
    private function getPurgeUri(): string
    {
        return 'cdn_loadbalancer/' . App::parseEnv($this->name) . '/cache-purge';
    }

    /**
     * Sends a request to the API.
     */
    private function sendRequest(string $uri, array $params = []): ?ResponseInterface
    {
        $response = null;

        $client = Craft::createGuzzleClient([
            'base_uri' => self::API_ENDPOINT,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $uri = App::parseEnv($this->namespace) . '/' . $uri;
        $options = !empty($params) ? ['json' => $params] : [];

        try {
            $response = $client->request('POST', $uri, $options);
        } catch (BadResponseException|GuzzleException) {
        }

        return $response;
    }

    /**
     * Returns the purge parameters.
     * https://docs.cloud.f5.com/docs/api/views-cdn-loadbalancer#operation/ves.io.schema.views.cdn_loadbalancer.CustomAPI.CDNCachePurge
     */
    private function getPurgeParams(string $pattern): array
    {
        return [
            // 'hard_purge' => [],
            'pattern' => $pattern,
            // 'soft_purge' => [],
        ];
    }
}
