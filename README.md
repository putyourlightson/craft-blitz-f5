# Blitz F5 Purger for Craft CMS

The F5 Purger plugin allows the [Blitz](https://putyourlightson.com/plugins/blitz) plugin for [Craft CMS](https://craftcms.com/) to intelligently purge pages cached on F5’s Edge CDN.

## License

This plugin is licensed for free under the MIT License.

## Requirements

This plugin requires [Craft CMS](https://craftcms.com/) 4.0.0 or later.

## Usage

Once installed, the F5 Purger can be selected in the Blitz plugin settings or in `config/blitz.php`.

```php
// The purger type to use.
'cachePurgerType' => 'putyourlightson\blitzf5\F5Purger',

```

Note that when purging multiple cached pages, only a single URI with a wildcard character after the longest common prefix is sent. This helps reduce the number of API requests made to the F5 CDN.

For example, if the URIs are:

- /foo/bar
- /foo/qux/baz

Then only a single API request will be sent with the URI pattern `/foo/*`.

The API request will send the URI pattern `/foo/*`.

---

Created by [PutYourLightsOn](https://putyourlightson.com/).
