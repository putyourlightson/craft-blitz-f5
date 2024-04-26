<p align="center">
    <svg width="130" clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg"><clipPath id="a"><path clip-rule="evenodd" d="m176 78.307h40v40h-40z"/></clipPath><clipPath id="b"><path clip-rule="evenodd" d="m212.507 87.014c-.245 1.014-.374 2.063-.61 3.137-3-.389-6.524-.674-10.482-.813-.318 1-.618 1.987-.962 3.061 6.622.406 9.841 2.151 11.749 4.209 1.58 1.782 2.356 4.139 2.143 6.511-.107 2.889-1.532 5.575-3.864 7.284-1.973 1.453-4.306 2.34-6.746 2.566-3.031.227-7.085-.494-7.947-1.023-.519-1.181-1.031-2.372-1.581-3.67-.075-.107-.115-.235-.115-.365 0-.209.103-.405.275-.524.59-.565 1.158-1.113 1.753-1.691.264-.258.556-.5.778-.129.816 1.258 1.579 2.411 2.345 3.562.87 1.282 2.193 2.45 5.086 2.256 2.258-.138 4.131-1.845 4.477-4.08.213-3.743-3.576-6.4-13.425-7.25 1.889-5.708 3.732-11.231 5.205-15.621 2.34.108 4.506.3 6.575.566 1.531.2 2.953.551 4.378.716-3.791-4.689-9.509-7.416-15.539-7.409-10.972 0-20 9.028-20 20-.006 4.265 1.36 8.421 3.893 11.852.847.006 1.434-.183 1.5-.549.051-.523.033-1.051-.054-1.569-.41-4.123-.54-8.269-.391-12.409-1.014.044-1.924.09-2.8.141.036-.785.078-1.529.141-2.3.862-.082 1.776-.155 2.779-.239.043-.677.092-1.331.15-1.992.45-4.05 4.716-6.528 8.268-7.485 1.08-.286 2.185-.468 3.3-.544.273-.009.568-.019.862-.019.675-.054 1.353.081 1.956.39.766.574 1.524 1.145 2.329 1.782.081.108.168.278-.032.574-.37.432-.727.846-1.1 1.29-.216.263-.576.193-.878.112-.634-.325-1.244-.623-1.862-.922-1.1-.593-2.322-.922-3.571-.962-.961.188-1.662 1.037-1.666 2.016-.111 1.715-.186 3.474-.249 5.379 2.238-.064 4.469-.1 6.824-.113v1.575c-.767.344-1.492.691-2.262 1.042-1.588.017-3.1.03-4.621.057-.07 4.532 0 9.046.2 13.22-.002.576.083 1.149.252 1.7.217.538 1.466.953 4.184 1.106.01.473.024.919.038 1.378-4.05-.099-8.087-.489-12.081-1.165 3.787 4.242 9.213 6.668 14.9 6.663 10.972 0 20-9.028 20-20 .006-4.031-1.212-7.97-3.493-11.293"/></clipPath><clipPath id="c"><path clip-rule="evenodd" d="m176 78h40v41h-40z"/></clipPath><path d="m44.525 13.656c-.003-.373-.229-.555-.405-.771 0 0-8.636-10.25-10.473-12.32-.095-.106-.1-.108-.305-.312-.137-.136-.33-.253-.484-.253h-23.596c-2.091 0-3.79 1.675-3.79 3.739v41.85c0 2.064 1.699 3.739 3.79 3.739h31.475c2.092 0 3.79-1.675 3.79-3.739z" fill="#333f4c" transform="matrix(1 0 0 1.01362 0 -.000361)"/><path d="m76.4 35.3c-.5-.9-1.5-1.5-2.6-1.5h-15.3l9.2-28.1c.3-.9.1-1.9-.4-2.7s-1.5-1.2-2.4-1.2h-24.2c-1.3 0-2.4.8-2.8 2.1l-16.5 50c-.3.9-.1 1.9.4 2.7.6.8 1.5 1.2 2.4 1.2h18l-2.3 35.2c-.1 1.4.8 2.7 2.2 3.1.3.1.6.1.8.1 1.1 0 2.1-.6 2.6-1.6l31-56.4c.5-.9.5-2-.1-2.9z" fill="#fec136" fill-rule="nonzero" transform="matrix(.127097 0 0 .127097 32.4665 35.2803)"/><g clip-path="url(#a)" transform="matrix(.674919 0 0 .674919 -107.285 -41.3497)"><g clip-path="url(#b)"><g clip-path="url(#c)"><path d="m171-6777.69h50v50h-50z" fill="#fff" fill-rule="nonzero" transform="translate(0 6851)"/></g></g></g></svg>
</p>

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
