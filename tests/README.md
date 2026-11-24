# Tests

`tests/phpunit/` is a PHPUnit suite on the WordPress core test library (the `wp-phpunit/wp-phpunit`
package). It loads the plugin the way WordPress loads a plugin, installs its tables from the schema,
seeds a sixty-row table and drives `Exports_Reports_Admin_UI` the way the report page, the exports and
the REST routes do, against a real database. Each test runs in a transaction the library rolls back.

## Running the suite locally

The suite needs a WordPress copy to load (its own `wp-config.php` is not read) and a database of its own:
the library drops every table with the `wptests_` prefix in it. Then:

```sh
composer install
composer test        # or vendor/bin/phpunit
```

`WP_MULTISITE=1 composer test` runs the suite as a network, which the cases about a network's super admins
need; they are skipped otherwise.

`tests/phpunit/wp-tests-config.php` reads its settings from the environment, with these defaults:

| Variable                   | Default                            | Meaning                                              |
|----------------------------|------------------------------------|------------------------------------------------------|
| `WP_TESTS_ABSPATH`         | `/tmp/wp/latest/`                  | The WordPress copy, with a trailing slash            |
| `WP_TESTS_DB_NAME`         | `wordpress_test`                   | The test database                                    |
| `WP_TESTS_DB_USER`         | `wp`                               |                                                      |
| `WP_TESTS_DB_PASSWORD`     | `wp`                               |                                                      |
| `WP_TESTS_DB_HOST`         | `localhost`                        |                                                      |
| `WP_PHP_BINARY`            | the PHP running the tests          | The PHP the library installs WordPress with          |
| `WP_PHPUNIT__DIR`          | `vendor/wp-phpunit/wp-phpunit`     | The test library (the bootstrap reads it)            |
| `WP_PHPUNIT__TESTS_CONFIG` | `tests/phpunit/wp-tests-config.php`| The configuration file (the bootstrap reads it)      |

The configuration fixes the rest: the `wptests_` prefix, `example.org`, `utf8mb4`, `WP_DEBUG`, an
uploads directory of its own (`wp-content/uploads-tests` under the WordPress copy, where the plugin's
export directory lives for the suite) and the direct filesystem method. The CTE case needs MariaDB 10.2
or MySQL 8; the `EXECUTE IMMEDIATE` case is MariaDB only and is skipped elsewhere.

## What the classes cover

- `Tests_Install` (`test-install.php`): the tables created with the site's character set and collation;
  a three-byte utf8 table converted on upgrade; the files the site logged moving from
  `wp-content/exports` on upgrade while other files stay, the old directory going once empty; the
  uninstall removing the tables, options, the cleanup event and the export files (then installing again
  for the tests that follow).
- `Tests_Report_Queries` (`test-report-queries.php`): report definitions through `get_data( true )`, the
  path the view and the exports share: a plain `SELECT`, a common table expression, a dynamic statement
  (`EXECUTE IMMEDIATE`) and a tab-indented definition, each with the `LIMIT %%LIMIT%%` placeholder; a
  search term's LIKE wildcards escaped; a paged view with a totals query counting every row; a search on
  an aliased column of a report with a totals query; an array filter value filtering on each value; a
  sort column kept only when the report has it, matched whatever its case.
- `Tests_Delimited_Exports` (`test-delimited-exports.php`): CSV streamed from an unbuffered result and
  buffered byte for byte the same, every value quoted, the byte order mark first, formula-like text
  neutralised; TSV; streaming only with plain columns, a custom display column through the buffered
  path; ASCII file names for a report named outside ASCII, ending in 24 random characters, a long name
  cut; a streamed export with a search term over a column expression containing `%d` matching the
  buffered one.
- `Tests_Writers` (`test-writers.php`): XLSX, XML and JSON exports checked for their labels and rows; the
  XLSX worksheet holding no formula for the seeded formula-like title or for a value or label that starts
  with `=`, `+`, `-` or `@`, and holding one when `exports_reports_export_escape_formulas` turns the
  handling off; a number column as a numeric cell holding the whole value; the list and both PDF
  engines formatting numbers through one function, and text in a number or
  decimal column left as it is; a two-page PDF with the default engine (tc-lib-pdf on PHP 8.2 and later,
  TCPDF below), the producer string naming it, and the default engine following the PHP version; the
  TCPDF engine handing stored markup to TCPDF as text (no tag of it, the text escaped) and keeping the
  markup of a display function.
- `Tests_Screen_Output` (`test-screen-output.php`): a link, an image and a script a table stores shown as
  text on the report page, in the title column and the others, and on the view screen (whose list of a
  related column's labels stays a list); the markup of a display function kept; the
  `exports_reports_html_values` filter opting one column back in, within the allowed tags.
- `Tests_Export_Files` (`test-export-files.php`): `export()` needing the link nonce and `remove_export`
  leaving the protection files alone; the export directory carrying `.htaccess` and `index.php`; the
  download function streaming an export and refusing anything else; `.txt` exports served and the daily
  cleanup having a handler; deactivation clearing the cleanup event and the next admin request putting
  it back; Clear Now removing export files only; an export removed only from the screen of its own report,
  by its log row; the log written in UTC and the cleanup counting the day in UTC under a site time zone
  ten hours from it, with the rows of an earlier version converted; an error about the directory naming
  its path to a user who manages reports only; no robots.txt line; the directory's check telling a served
  directory from a protected one, an unreachable site and a directory outside the web root, and Site
  Health and the Settings page reporting what it finds.
- `Tests_REST_API` (`test-rest-api.php`): the report refused without the token or a user; full and paged
  data with the token parameter and the bearer token; the report access rules for users; export and
  download through the routes, the export logged; download authorisation following the log row and the
  access rules; a disabled report hidden; an empty token saved from the settings page turning token
  access off.
- `Tests_Access` (`test-access.php`): the matrix of who reaches what. The rows are a subscriber, a user
  whose role the group names, a user with `exports_reports_view`, one with `exports_reports_settings`, an
  administrator and nobody; the columns are the report page, the REST data and export routes, the
  admin-ajax export, the download, the removal, the Manage screens, Detect Fields and the Settings page.
  An administrator whose role has lost the capabilities refused at every one on a single site, and a
  fresh install putting them back; on a network (skipped on a single site) a super admin reaching
  everything, a site's administrator getting the Manage screens as lists, with no form, no save and no
  Detect Fields, and the `exports_reports_site_admins_edit_reports` filter handing the reports back to
  the sites; administrators holding `exports_reports_full_access` through their
  role, not user meta.
- `Tests_Display_Functions` (`test-display-functions.php`): a Display function, and the helper of a
  `{@field,helper}` tag, called only when the site lists it through `exports_reports_display_callbacks`
  (not a function of another plugin, of WordPress or of PHP, listed or not); the functions the stored
  definitions name recorded once, when the list is first asked for, and none after; the notice naming
  them to a user who manages reports until one dismisses it with its nonce; the field editor flagging a
  Display function that is not listed.
- `Tests_Hooks` (`test-hooks.php`): the cases that attach callbacks to `exports_reports_*` hooks (their
  arguments one by one, the screen last) and to the `wp_admin_ui_*` names (the array form): a
  multi-statement definition prepared through `exports_reports_get_data_sql`, the count query through
  `exports_reports_get_data_sql_count`, a query the callback declines, `exports_reports_allowed_callback`
  admitting a PHP function, the `wp_admin_ui_` names of the versions before 0.9.5 reaching their
  callbacks as deprecated aliases, the condition `exports_reports_data_filter_sql` sees for an array
  filter value, `exports_reports_export_escape_formulas` turning the formula wrapping off, and
  `exports_reports_export_pdf_engine` selecting TCPDF.
- `Tests_Request_Handling` (`test-request-handling.php`): the report field editor rendering the saved
  fields and saving the posted rows in order, unslashed and sanitized; `save()`, `reorder()` and the
  settings form carrying and verifying their nonces; request values unslashed and sanitized on the way in;
  a page size that is not offered (0, 7, 100000000) falling back before the query runs, the sizes of the
  `exports_reports_limit` filter and the screen's own size taken; 85 rows at 10 a page as 9 pages with
  their links (issue #32), each address escaped; `debug=1` answering a user who manages reports only and
  wpdb's error display restored.

`tests/phpunit/includes/` holds the base class and the helpers the classes share: the seeded table
(created before each class, dropped after it), a report definition set up the way `exports_reports_view()`
sets it up, group and report rows, the export files, the REST requests and the admin-ajax handlers, and
the listing of the suite's own display functions. A request for a file of the export directory by its
address is answered with a 403 inside the suite, so no test reaches the network. The base class is declared for the fixture methods
of the test library in use (`setUp()`/`tearDown()` on WordPress 5.7 and 5.8, `set_up()`/`tear_down()` from
5.9), and the test classes override `prepare()` and `clean()` instead.

## CI

`.github/workflows/phpunit.yml` runs the suite on every pull request that touches PHP, the tests, the
vendor tree or the fonts, on a matrix of five jobs: WordPress 5.7 on PHP 7.2 with MariaDB 10.11 and
WordPress 5.8 on PHP 7.3 with MySQL 8.0 (the plugin's floors, with PHPUnit 7.5 and the matching
`wp-phpunit` version), the current WordPress on PHP 8.3 with MariaDB 11.4 and with MySQL 8.4 (PHPUnit
9.6, the `wp-phpunit` version of the downloaded core), and the MariaDB 11.4 job once more as a network
(`WP_MULTISITE=1`). Each job downloads WordPress with WP-CLI, installs
PHPUnit, the Polyfills and the library in a Composer project of their own (the plugin's dev dependencies
need PHP 8.2, and its runtime vendor tree is committed) and runs `phpunit.xml.dist` with the variables
above pointing at the service database.
