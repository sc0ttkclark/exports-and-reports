# Exports and Reports

A WordPress plugin that turns any MySQL `SELECT` query into a report page in the admin. You write the query; the plugin adds the search box, the filters, the sorting, the pagination and the export buttons, and your users export to CSV, TSV, pipe-separated TXT, XLSX, XML, JSON or PDF, from the admin screens or through the REST API.

- **Install:** [wordpress.org/plugins/exports-and-reports](https://wordpress.org/plugins/exports-and-reports/), or a zip from the [releases](https://github.com/sc0ttkclark/exports-and-reports/releases)
- **Requires:** WordPress 5.7 and PHP 7.2
- **User documentation:** [`readme.txt`](readme.txt) holds the FAQ, the placeholders a query takes, the hooks and the changelog; the wordpress.org listing is made from it
- **Bugs and requests:** the [issues](https://github.com/sc0ttkclark/exports-and-reports/issues); a security problem goes through [`SECURITY.md`](SECURITY.md) instead

## Who can do what

Reports are SQL that administrators write, and the plugin runs it as written. Only users with the plugin's settings capability can create or edit a report. The people a report is shared with supply search terms, filter values, a sort column and a page, which reach the query as prepared values; they cannot change its SQL. The readme's "Who can see what?" answer has the details.

## Working on the plugin

The bundled libraries in `vendor/` are committed, so a checkout placed in `wp-content/plugins/` runs without Composer. The development tools need Composer and Node:

```sh
composer install   # the PHP tools; also runs Strauss, which rebuilds vendor/vendor-prefixed
npm ci             # the JS and CSS linters (Node version in .nvmrc)
```

| Check | Command | Notes |
| --- | --- | --- |
| Tests | `composer test` | PHPUnit on the WordPress test library; needs a WordPress copy and a database, see [`tests/README.md`](tests/README.md) |
| Coding standards | `vendor/bin/phpcs --standard=phpcs.xml` | WordPress Coding Standards |
| Static analysis | `vendor/bin/phpstan analyse -c phpstan.neon` | `phpstan-legacy.neon` on PHP below 8.2 |
| JS and CSS | `npm run lint` | |
| Translation template | `bin/make-pot.sh --check` | `bin/make-pot.sh` rewrites `languages/exports-and-reports.pot` |
| Vendor tree | `bin/check-vendor.sh` | proves `vendor/` is what `composer.lock` and Strauss produce; needs network |

Every check runs on pull requests, along with the PHP compatibility sniffs and [Plugin Check](https://wordpress.org/plugins/plugin-check/) (`.github/workflows/`).

[`CONTRIBUTING.md`](CONTRIBUTING.md) says what a pull request should carry.

## Releasing

1. The version is declared in four places that must agree: the plugin header and the `EXPORTS_REPORTS_VERSION` constant in `exports-and-reports.php`, the `Stable tag` in `readme.txt`, and `package.json`. `bin/check-version.sh 1.2.3` checks them.
2. `readme.txt` has a changelog section for the version, and an upgrade notice when sites need to know something before they update.
3. The "Release zip" workflow builds the zip from the tree `git archive` gives, checks it, and attaches it to a draft release: `gh workflow run release.yml --ref main -f tag=1.2.3`, with `-f publish=true` to publish it in the same run.
4. Publishing the release starts "Deploy to WordPress.org", which commits the tag to the plugin's SVN repository with the `SVN_USERNAME` and `SVN_PASSWORD` secrets, and the contents of `.wordpress-org/` as the listing's icon, banner and screenshots.

## License

GPL v2 or later; see [`LICENSE`](LICENSE). The bundled libraries keep their own licenses, in their folders under `vendor/vendor-prefixed/`.
