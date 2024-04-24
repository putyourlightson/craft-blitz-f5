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

---

Created by [PutYourLightsOn](https://putyourlightson.com/).
