=== Exports and Reports ===
Contributors: sc0ttkclark, dxdc
Donate link: https://github.com/sponsors/dxdc
Tags: export, reports, csv, excel, sql
Requires at least: 5.7
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn any MySQL SELECT query into a report your users can search, filter and export to CSV, XLSX, PDF, JSON or XML.

== Description ==

Exports and Reports turns any MySQL SELECT query into a report page in the WordPress admin. You write the query; the plugin adds the search box, the filters, the sorting, the pagination, the display formatting and the export buttons around it.

Create your Groups, add your Reports to them, choose which user roles may see each one, and hand them off to your clients or colleagues. Reports export to CSV, TSV, pipe-separated TXT, XLSX, XML, JSON and PDF, from the admin screens or through the REST API with a token, for cronjobs and scripts.

Requires PHP 7.2 and WordPress 5.7 or later. PDF exports render with tc-lib-pdf on PHP 8.2 and later and with TCPDF below that.

Report queries are written by administrators and run as written: only users with the plugin's settings capability (administrators by default; on a network, its super admins) can create or edit a report. The plugin adds its paging, sorting and filters around the query, with every request value passed as a prepared placeholder and the sort column checked against the report's columns; readers of a report cannot change its SQL.

Please submit bug reports or contribute your own enhancements/fixes on [GitHub](https://github.com/sc0ttkclark/exports-and-reports).

== Frequently Asked Questions ==

**What are Groups?**

Groups are groupings of Reports that are given their own menu item in the "Reports" menu.

**What is a Report?**

A Report is defined by a Custom MySQL query and can be configured to display however you wish using additional field definitions. Exports can be disabled per report.

**My report isn't working**

As a user who may manage reports (the settings capability or full access), add `&debug=1` to the end of the report URL to see the query the plugin runs, with the database's errors; take that query to your own MySQL client or phpMyAdmin to see whether the error is in your own query.

= Why does my report show no page links? =

A query with a LIMIT of its own and no `%%LIMIT%%` placeholder shows what it returns as one page: take the LIMIT out, or write `LIMIT %%LIMIT%%` where the page belongs. Otherwise the report pages by the size chosen under "Show per page"; a size an address asks for that is not among them is not taken, and the report's own size serves.

= Where do the paging, the row count, a sort and a filter go in my query? =

Into the query's main SELECT: the one at the top level, after any WITH clauses, or inside the string of an `EXECUTE IMMEDIATE`. The row count (`SQL_CALC_FOUND_ROWS`) goes right after it, a sort from a column header goes before its own ORDER BY, a search or a filter joins its own WHERE (or HAVING, for a grouped column), and the page's LIMIT goes at its end. Subqueries, the queries of WITH clauses and window functions are left alone. Where you want them elsewhere, write `%%WHERE%%`, `%%HAVING%%`, `%%ORDERBY%%` or `%%LIMIT%%` at that spot. A query with a LIMIT of its own and no `%%LIMIT%%` shows what it returns as one page.

A search or a filter names a column by what the SELECT list makes of it. A field that is the alias of a column (`p.post_title AS title`) is searched as that column, and one that is the alias of an expression (`CONCAT( first, ' ', last ) AS name`) as the expression, both in WHERE; an alias of an aggregate (`SUM( total ) AS revenue`) can only be named in HAVING, so a search that includes one goes into HAVING with all its columns. A field's Real field, where you fill it in, is used as you wrote it. A window function's result (`ROW_NUMBER() OVER ( ... )`) and an expression that sets a session variable are left out of the search: no condition of the same query can name them.

= Can a report run more than one statement? =

Yes. End each with a semicolon: session variables first (`SET @day = CURRENT_DATE();`), a dynamic statement if the report builds one (`EXECUTE IMMEDIATE CONCAT( ... );`), and last the query that returns the rows. They run in order on one connection, so the last statement reads what the ones before it set up. The plugin's paging, search and filters go into the last statement only.

= How do scripts and cronjobs read a report? =

Through the REST routes under `/wp-json/exports-reports/v1/reports/ID/` (the Settings page shows them), as a logged-in user who may view the report (cookie and nonce, or an application password) or with the API token from the Settings page, sent as the header `Authorization: Bearer TOKEN`. The routes also take the token as a `token` query parameter until the Settings page's "Take the token in the Authorization header only" box is ticked; a token in an address ends up in server logs, so tick the box once your scripts send the header. The token is stored as a hash, like a password, so it is shown once, when you generate it or set one of your own (at least 24 characters, stored as typed); generate a new one if it is lost, or turn token access off. A fresh install has token access off.

Ten wrong tokens from one address within a quarter of an hour make the routes answer that address's token requests with 429 for as long. The `exports_reports_token_failure_limit` filter changes the number, and on a site behind a reverse proxy the `exports_reports_token_client_address` filter names the client's address.

= Why does the row count lag behind the rows? =

The count ("1,234 items") is kept for a while once a page of the report has run: five minutes unless the Settings page's Row Count Cache says otherwise, per query, search and filters. Counting runs the query over every row it matches, which on a slow report costs as much as the page itself, so the pages that follow run without it while the count is kept. Rows added or removed meanwhile show in the count once it is counted again; the Settings page's Forget the Counts Now button makes every report count again at once, and Off keeps no counts. Code can adjust the time per report with the `exports_reports_count_cache_ttl` filter.

= How do I fill in the fields of a report? =

Leave the field editor empty and the report shows every column the query returns, as text. To name them, set their data types or hide, search and filter on some, add the fields: the Detect Fields from the Query button runs the query for one row and adds a field for each column of the result that is not listed yet, with the data type that fits the column (dates, numbers, decimals, flags); Add Field adds one by hand.

= How do I format a column with a function of my own? =

Put the function's name into the field's Display function (under Advanced options) and list it from your plugin or theme:

`add_filter( 'exports_reports_display_callbacks', function ( $callbacks ) { $callbacks[] = 'my_plugin_format_money'; return $callbacks; } );`

The function receives the value, the row, the column, the column's attributes and the report screen, and returns what the cell shows; HTML it returns keeps the tags post content may have. A report calls only the functions on this list, whatever a field names: a definition is data, and it should not be able to reach every function WordPress, a plugin or a theme defines. The functions your reports named when the site was updated to 1.3.0 were listed then, and the field editor says which of a report's Display functions are not called.

= Can I limit the export formats? =

Yes. The Settings page lists the formats the export buttons offer on every report; untick the ones the site does not need. A report can narrow that list further in its own settings (Export Formats), but not widen it. A format that is not offered is refused by the export and by the REST route, not only hidden. CSV, TSV and TXT are written row by row and suit very large reports; XLSX, XML, JSON and PDF hold the whole report in memory while they are written. Code can adjust a report's list with the `exports_reports_report_export_formats` filter.

= Can a value in an export run as a formula in a spreadsheet? =

No. A spreadsheet that opens a CSV, TSV or TXT file evaluates a value that starts with `=`, `+`, `-` or `@`, so those exports wrap such a value in a text formula that shows it and stays inert (numbers are left alone). The XLSX export writes every text value as a text cell, exactly as stored. A site that writes formulas into its reports on purpose turns this off with the `exports_reports_export_escape_formulas` filter, for all four formats.

= Can an export write numbers and dates the way my country does? =

Yes, when the person who exports asks for it. The Format menu beside the export buttons starts on Raw values, which is what every version of the plugin has written (`1,234.50`, `2026/03/07 2:05:09 PM`), and offers your language, the site's and every other language installed on the site. Choose Italian, and a CSV, TSV, TXT or PDF export holds `1.234,50` and `07/03/2026`; the CSV file separates its values with semicolons, because that is how a spreadsheet in a country of comma decimals reads a CSV file. The menu remembers what each user last chose for each report, and a report can set where the menu starts (Default Export Format, in the report's settings).

For a team in more than one country, share the XLSX export. With any language chosen it holds numbers as numbers and dates as dates, and Excel, LibreOffice and Google Sheets each show them in the regional format of whoever opens the file. XML and JSON are read by programs and always hold the raw values.

Nothing changes for a script that asks for nothing: an export through the REST API holds the raw values unless the request adds a locale, as in `export?type=csv&locale=it_IT`.

PHP's intl extension writes the numbers and dates of any language WordPress knows. Without it, the plugin goes by the language pack WordPress has installed for the language (its number separators and its numeric date); a language with no pack is then written as raw values.

On the report pages and in PDF exports, numbers take the separators of the language WordPress runs in, and dates stay as `2026/03/07` unless you tick "Use the site's date and time formats" on the Settings page.

Not covered: time zones (a date is written as it is stored), currency symbols, calendars other than the Gregorian one, and right-to-left layout in the PDF. Code can decide the language of an export with the `exports_reports_export_locale` filter, the menu's choices with `exports_reports_export_locales` and the CSV delimiter with `exports_reports_export_csv_delimiter`.

= My data holds HTML. Why does the report show it as text? =

Because the values a report reads are shown as what they are. A table often holds text someone else typed (a form entry, a profile field), and a link or an image in it would otherwise render in the admin as if the site had put it there. Markup your own code writes still renders: what a field's Display function returns keeps the tags post content may have (the `exports_reports_allowed_html` filter extends them). For a column whose stored values are markup of the site's own, opt it back in:

`add_filter( 'exports_reports_html_values', function ( $html, $column, $attributes, $admin ) { return 12 === $admin->report_id && 'summary' === $column ? true : $html; }, 10, 4 );`

PDF exports print text as well; on PHP below 8.2, where TCPDF renders them, a Display function's output keeps its markup there too.

= Where do the export files go, and who can download them? =

In `wp-content/uploads/exports-and-reports` by default, a folder of the uploads directory; define `EXPORTS_REPORTS_EXPORT_DIR` and `EXPORTS_REPORTS_EXPORT_URL` in wp-config.php to move it, for example outside the web root. Every download goes through the plugin after a nonce or API token check, so the directory never needs to be reachable by URL. The plugin writes an .htaccess that keeps Apache from serving it; nginx ignores .htaccess, so either add the rule to the server block:

`location ~* /wp-content/uploads/exports-and-reports/ { deny all; }`

or define `EXPORTS_REPORTS_EXPORT_DIR` in wp-config.php with a writable directory outside the web root, which needs no server rule at all. The Settings page shows the directory in use.

The plugin checks whether the rules hold: it writes a file into the directory, asks the site for it by its address and removes it. Tools > Site Health reports a directory the server serves as a critical issue, and the plugin's Settings page shows the same finding. An export is removed a day after it was written, or sooner with the button under its message; its name ends in 24 random characters.

= Who can see what? =

The plugin registers three capabilities: `exports_reports_full_access` (everything), `exports_reports_settings` (the Reports Admin screens) and `exports_reports_view` (every report). A group or a report can also be opened to roles on its edit screen, and a role can be given `exports_reports_view_group_ID` or `exports_reports_view_report_ID` for one group or one report. The administrator role receives the three capabilities when the plugin is activated (and on the upgrade to 1.0); a roles plugin such as Members can take them away or give them to other roles, and the plugin does not put them back until it is activated again. On a single site the capabilities alone decide: an administrator whose role has lost them is refused like anyone else. Super admins of a network always have full access.

On a network the groups and the reports are written by its super admins, because a report's query reaches the tables of every site. A site's administrators view the reports, get Manage Groups and Manage Reports as lists and keep the Settings page. A network that leaves the reports to its sites says so:

`add_filter( 'exports_reports_site_admins_edit_reports', '__return_true' );`

= Which hooks does the plugin offer? =

Every hook is documented where it runs; these are the ones sites use most. Filters receive the value first and the report screen (`Exports_Reports_Admin_UI`) last.

* The report query: `exports_reports_get_data_sql( $sql, $full, $admin )` and `exports_reports_get_data_sql_count( $sql, $full, $admin )` right before the query and its totals query run (return false to run nothing); `exports_reports_get_data_total( $total, $full, $admin )` for the row count and `exports_reports_count_cache_ttl( $seconds, $admin )` for how long it is kept; `exports_reports_data_filter_sql( $condition, $filter, $column, $field, $related_field, $value, $admin )` for the condition a filter adds.
* Exports: `exports_reports_export_file( $file, $type, $extension, $items, $admin )` for the file name; `exports_reports_export_escape_formulas( $escape, $admin )` for the CSV, TSV, TXT and XLSX exports; `exports_reports_export_locale( $locale, $admin )` for the language an export's numbers and dates are written for (false for the raw values), `exports_reports_export_locales( $locales, $admin )` for the choices of the Format menu, `exports_reports_export_csv_delimiter( $delimiter, $locale, $admin )`, `exports_reports_export_western_digits( $western, $locale )` and `exports_reports_use_intl( $use )`; `exports_reports_export_pdf_engine( $engine )` and `exports_reports_export_pdf_report_file( $file, $admin )`; the actions `exports_reports_pre_export( $admin )`, `exports_reports_post_export( $file, $admin )`, `exports_reports_pre_remove_export( $file, $path, $admin )`, `exports_reports_post_remove_export( $file, $removed, $path, $admin )` and `exports_reports_export_download`.
* The screens: `exports_reports_report_options( $options, $report_id )` and `exports_reports_options( $options, $admin )` for a screen's settings; `exports_reports_setup_columns( $columns, $which, $init, $admin )` and `exports_reports_setup_column( $attributes, $column, $filterable, $admin )`; `exports_reports_row_actions( $links, $row, $admin )`; `exports_reports_limit( $sizes, $admin )` for the page sizes; `exports_reports_allowed_html( $allowed )` for the HTML a display callback may return and `exports_reports_html_values( $html, $column, $attributes, $admin )` to show a column's stored values as HTML instead of text; `exports_reports_display_callbacks( $callbacks )` for the functions a field definition may name as its Display function, and `exports_reports_allowed_callback( $allowed, $callback, $admin )` to decide for one name; the actions `exports_reports_pre_init( $options )`, `exports_reports_post_init( $options, $admin )`, `exports_reports_pre_save( $create, $admin )`, `exports_reports_post_save( $id, $data, $create, $admin )`, `exports_reports_pre_delete( $id, $admin )`, `exports_reports_post_delete( $id, $admin )`, `exports_reports_post_reorder( $id, $admin )`.
* The REST token: `exports_reports_token_header_only( $header_only )` for whether the token is taken in the header only; `exports_reports_token_failure_limit( $limit )` for how many wrong tokens an address may send within a quarter of an hour; `exports_reports_token_client_address( $address )` for the address they are counted for.
* Capabilities: `exports_reports_capabilities( $caps )`; `exports_reports_site_admins_edit_reports( $allowed, $site_id )` to let the sites of a network write their own groups and reports.

Before 1.0 the `exports_reports_*` and `wp_admin_ui_*` hooks of the report screen passed their arguments as one array; callbacks on the `wp_admin_ui_*` names still receive that array, with a deprecation notice.

== Installation ==

1. In your site's admin, go to Plugins >> Add New, search for "Exports and Reports" and click Install Now
1. Or download the zip from [wordpress.org](https://wordpress.org/plugins/exports-and-reports/) or the [GitHub releases](https://github.com/sc0ttkclark/exports-and-reports/releases), and upload it through Plugins >> Add New >> Upload Plugin or unpack it into your `wp-content/plugins/` folder
1. Activate this plugin
1. Create your Groups and Reports under Reports Admin; the Settings page shows the export directory and the API URLs

The plugin's bundled libraries (`vendor/`) are part of the zip and of the repository, so a checkout works without Composer.

== Features ==

= Administration =
* Create and Manage Groups
* Create and Manage Reports
* Limit which User Roles have access to a Group or Report
* Ability to clear entire export directory (based on logged export files)
* Daily Export Cleanup via wp_cron
* The export directory is not served by the web server; downloads go through the plugin after an access check, and Site Health reports a server that serves it anyway
* Deletes its tables, options and export files on uninstall

= Reporting =
* Filter by Date
* Automatic Pagination, with the row count kept for a while on slow reports
* Show only the fields you want to show; the field editor detects them from the query
* Pre-display modification through custom defined function per field or row

= Exporting =
* CSV - Comma-separated Values (w/ Excel support)
* TSV - Tab-separated Values (w/ Excel support)
* TXT - Pipe-separated Values (w/ Excel support)
* XLSX - Excel format, using [PHP_XLSXWriter](https://github.com/mk-j/PHP_XLSXWriter)
* XML - XML 1.0 UTF-8 data
* JSON - JSON format
* PDF - PDF printer friendly views, using [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf) on PHP 8.2 and later and [TCPDF](https://tcpdf.org/) below that
* Numbers and dates in the format of a language, chosen from a menu when exporting: comma decimals, the day before the month, semicolons in the CSV file, and number and date cells in XLSX

= REST API / Cronjob =
* Run the Export action for a specific report to any supported export type, and download the file; add `locale=it_IT` for the numbers and dates of a language
* Get paginated / full data from a report in JSON format
* Routes under /wp-json/exports-reports/v1/reports/ID/ (data, export, exports/FILE), for a logged-in user who may view the report or for the API token from the Settings page, sent as "Authorization: Bearer TOKEN" (or as ?token= until the Settings page limits it to the header, which keeps the token out of URLs and server logs); the Settings page shows the URLs. The token is stored as a hash and shown once, when it is generated; repeated wrong tokens from one address are refused for a while

== Screenshots ==

1. View the report, search and filter it, page through the records, and export them as raw values or in the number and date format of a language
2. Create and manage report groups that can contain their own sets of reports
3. Create and manage reports
4. Specify the SQL query that selects the data; placeholders put the search, the filters, the sort and the page where you want them, and an optional totals query feeds the Totals row
5. Specify the fields to display on the report and what kind of data they are, or detect them from the query
6. Exports run in the background: the message with the download and the button that removes the file
7. Set advanced field options to further customize how fields will be displayed, exported, searched, filtered, or related to other tables
8. Manage plugin settings: the export formats, the dates of the screens, the row count cache, the check of the export directory and the API token with its URLs

== Changelog ==

= 1.3.0 =
* On a single site the plugin's capabilities alone decide who views and manages reports. The super-admin shortcut, which WordPress extends to every administrator of a single site, now applies on a network only, so a role that loses the capabilities loses the access. The Settings page follows the same capabilities instead of `manage_options`.
* The Manage Groups and Manage Reports screens check the capability themselves.
* Site Health and the Settings page check whether the web server serves the export directory, which only Apache protects on its own, and give the rule to add.
* Export file names end in 24 random characters instead of 8.
* The export log and the daily cleanup use UTC. An export was removed earlier or later than a day after it was written, by the site's distance from UTC.
* An export can be removed only from the screen of its own report.
* An error about the export directory shows its path on the server only to users who manage reports.
* robots.txt no longer names the export directory; the `exports_reports_robots_txt()` function is gone.
* Development: the PHPUnit suite runs on pushes to `main`, and once more as a network.
* Development: Plugin Check runs on pushes to `main` and in the release workflow, and `tests/README.md` lists the findings the code suppresses.
* A token typed into the Settings page needs at least 24 characters and is stored as typed; `sanitize_title()` used to rewrite it. Tokens already in use keep working.
* A new Settings box, "Take the token in the Authorization header only". Until it is ticked, the `token` query parameter still works.
* Ten wrong tokens from one address within a quarter of an hour are answered with 429 for as long. Filters: `exports_reports_token_failure_limit` and `exports_reports_token_client_address`.
* A field's Display function, and the helper of a `{@field,helper}` tag, is called only when the site lists it through the new `exports_reports_display_callbacks` filter; a definition could name any function before. The functions your reports use at the time of the update are listed for you, and a notice names them.
* On a network (multisite), groups and reports are created and edited by super admins only: a report's SQL reaches every site's tables. Site administrators still view the reports and keep the Settings page. `add_filter( 'exports_reports_site_admins_edit_reports', '__return_true' );` hands the editing back to the sites.
* Search and filters work on aliased columns (#31). The alias of a column written with its table (`v.title AS name`) or of an expression is searched as what it stands for; the alias of an aggregate goes into HAVING, with the search's other columns.
* An export can be written for a language (#9). A Format menu beside the export buttons, which starts on Raw values, gives the CSV, TSV, TXT and PDF exports that language's numbers and dates, semicolons in a CSV file where decimals take a comma, and number and date cells in XLSX. The choice is remembered per user and report, and a report can set its default. XML and JSON always hold the raw values.
* An export through the REST API holds the raw values unless the request adds `locale=it_IT`.
* New filters: `exports_reports_export_locale`, `exports_reports_export_locales`, `exports_reports_export_csv_delimiter`, `exports_reports_export_western_digits` and `exports_reports_use_intl`.
* On the report pages and in PDF exports, numbers take the separators of the site's language, which changes nothing on an English site. A new Settings box, "Use the site's date and time formats", does the same for dates. The other export formats are not affected.
* A negative number with a thousands separator (`-13,506.75`) is no longer wrapped like a formula in CSV, TSV and TXT.
* The report form shows the group a report is in. It showed the first group, and saving the form moved the report there: check the group of reports edited on earlier versions.

= 1.2.9 =
* Security: the XLSX export writes a value that starts with `=` as text. It was written as a formula, so text a report merely stores (a title, a name someone typed into a form) ran in the spreadsheet of whoever opened the export. The CSV, TSV and TXT exports have neutralized such values since 0.9.2; XLSX, added in 0.8.0, never did. The cell shows the value as stored, with nothing added to it. The `exports_reports_export_escape_formulas` filter, which turns the handling off for the delimited exports, decides for XLSX as well. Versions 0.8.0 to 1.2.8 are affected.
* Security: on PHP below 8.2, where PDF exports render with TCPDF, the values a report reads are printed as text. They went into TCPDF's HTML renderer with the tags post content may have, so an `<img>` stored in a table a report reads made the server fetch its address, or read a local image path, when someone exported the PDF. What a display function returns keeps its markup. The tc-lib-pdf engine of PHP 8.2 and later has always printed text and is not affected.
* Security: the report page and the view screen show the values a report reads as text. They were printed with the tags post content may have, so a link or an image stored in a table a report reads (a field a subscriber or a visitor filled in) rendered in the admin as the site's own; no script ran. What a Display function returns keeps its markup, and the new `exports_reports_html_values` filter opts a column of a report back in where the stored values are markup of the site's own.
* The page size an address asks for is taken only when it is one of the sizes offered (10, 25, 50, 100 and 200, or what the `exports_reports_limit` filter returns), and before the query runs. It was checked where the sizes are printed, after the query had run with it: `limit=100000000` read every row into one page, and a size that is not offered showed pages of one size under page links counted for another. A screen set up with a size of its own offers that size too. The `exports_reports_limit` filter runs when the screen is set up as well as when the sizes are printed.
* `&debug=1` answers a user who may manage reports (the settings capability or full access) instead of one with `manage_options`, and the database's error display goes back to what it was once the queries have run.
* The previous-page link of the paging escapes its address as an address, and the title of the last-page link lost a stray quote. The paging of 85 rows at 10 a page (issue #32) has its test: 9 pages, each linked.
* The XLSX export writes a number column as a numeric cell holding the whole value. A value of 1,000 or more came out as the digits before its first thousands separator (1,400 as 1), and as text.
* A number or decimal column whose value is no number (text, an empty string) shows the value as it is, on the list, on the view screen, in the totals and in every export; on PHP 8 formatting it stopped the report page and the export, and the PDF export showed it as 0. One function formats these columns everywhere, so the PDF export and the view screen round a fraction in a number column as the list does, where they cut it off.
* The installation steps install the plugin from wordpress.org, where it is listed; the release zip from GitHub stays as the alternative.
* The short description, the opening of the description and the plugin header say what the plugin does in one sentence; the tags are the common forms (export, reports, csv, excel, sql). The feature list no longer offers the WP Admin UI class to other plugins: the class has carried the plugin's own name since the rewrite.
* The plugin header no longer carries the `GitHub Plugin URI` and `Primary Branch` headers of Git Updater: updates come from wordpress.org.
* The package metadata names both maintainers, and the translation template and the release workflow point at the repository the plugin lives in (sc0ttkclark/exports-and-reports); composer.json no longer suggests WP-CLI for another plugin's commands.
* The repository has a README, the GPL's text in a LICENSE file, a security policy (SECURITY.md) and contributing notes (CONTRIBUTING.md); the license ships in the zip, the other three stay in the repository.
* The wordpress.org listing has an icon and a banner (`.wordpress-org/`; their SVG sources are in `.github/artwork/`).

= 1.2.8 =
* The XML export stays parseable whatever the report returns: a value holding a CDATA terminator is split across two sections, and a column name XML does not allow becomes a valid element name.
* The XML and JSON exports report a file that could not be written as an error instead of showing the success message.
* The plugin header names both maintainers.

= 1.2.7 =
* The Manage Groups and Manage Reports lists sort as the Reports menu does, by weight and then by name, so groups or reports of equal weight no longer show in one order on the list and another in the menu.

= 1.2.6 =
* Spelling is American English throughout the readme, the release notes and the code comments.
* The release notes of a release cover every version since the last published one, each with its upgrade notice, so the changelog of versions that stayed drafts reaches the releases page; the workflow also takes a notes_ref input, with which a draft's notes are rewritten from that ref's readme.

= 1.2.5 =
* The bars above and below a report, the report selector and the filter row lay out as flex rows with every control centered on the line, so an admin theme that changes the height of buttons or inputs (Flatty makes buttons 32px tall, say) leaves the "Export:" label, the row count and the page links centered. The count and the page links sat 6px above the buttons on a one-page report before, with or without a theme. The bar under a one-row report no longer ends in a lone separator.

= 1.2.4 =
* The hash of the file is gone again from the version the stylesheet and the scripts are served with: the plugin's version, which changes with every release, is enough, and the hash cost a read of each file on every admin page.

= 1.2.3 =
* A report whose fields are none of them searchable (or are dates and times, which the search skips) and has no filter shows no search box: it did nothing but push the table down. The page field of the pagination keeps its form.
* The "Export:" label sits on the center line of its buttons.
* The "Loading the report…" line goes as soon as the report is in the page, by a line of script printed right after it, instead of waiting for every script of the page to load.
* The plugin's stylesheet and scripts are served with a hash of the file in their version, so a browser or a proxy fetches a changed file afresh even within one plugin version.

= 1.2.2 =
* The pagination says when a row count comes from an earlier page view ("counted 3 mins ago"); the Manage Groups and Manage Reports screens keep no count, and the uninstall removes the kept counts.
* Detect Fields marks text columns searchable and the others not: a LIKE over a date or a number finds nothing worth its cost.
* The report form's second query is labeled for what it does, "SQL Query for Totals": its row feeds the Totals row under the last page and the totals of the REST data; the row count comes from the report query.
* The custom-delimiter export is gone. It was reachable only through an address, and no address has written an export since 1.1.2; CSV, TSV and the pipe-separated TXT remain.
* A Copy button beside the token the Settings page shows once, the Save Settings button through the core helper, and the screenshots retaken.

= 1.2.1 =
* The example group and its two reports are written on a first install only when the groups and reports tables are empty. A site whose version option was missing while it had groups and reports of its own got the examples added on the next admin visit.

= 1.2.0 =
* The API token is stored as a hash, like a password, and shown once when it is generated or set. The Settings page has buttons to generate a token and to turn token access off, and a field for a token of your own; the example addresses carry a placeholder. A token an earlier version stored in the clear is replaced by its hash on the first request that uses it, so scripts and cronjobs keep working through the update. A fresh install has token access off until a token is generated.
* The row count of a report page ("1,234 items") is kept for five minutes (Settings: Row Count Cache, from off to an hour) for the same query, search and filters. The count runs with the first page over every row the query matches, which costs a slow query as much again; while a count is kept, the page views that follow run the query without it. Rows added or removed meanwhile show in the count once it is counted again, which the Settings page's Forget the Counts Now button makes happen at once. The `exports_reports_count_cache_ttl` filter adjusts the time per report.
* The report form's field editor has a Detect Fields from the Query button: it runs the query for one row and adds a field for each column of the result that is not listed yet, with the data type that fits the column.
* The link that removes an export under the export's message is a button that posts, sent in the background like the exports themselves, so the page's address never names a file; the removal runs no query. A removal link from before this version, opened from the history, is noted and removes nothing.
* The translation template ships as languages/exports-and-reports.pot, with the Domain Path header; bin/make-pot.sh rewrites it and the workflow checks it is current.

= 1.1.4 =
* The plugin's stylesheet loads in the head of its pages. It was enqueued from the page itself, which put it in the footer, so a report rendered unstyled first and snapped into shape when the stylesheet arrived.
* On a report page the admin frame is sent as soon as WordPress has written it, before the report's query runs, and the loading note is a plain line under the report selector.

= 1.1.3 =
* The row count, a sort from a column header, a search or a filter and the page's LIMIT go into the query's main SELECT, found by depth: the one after the WITH clauses, or inside an `EXECUTE IMMEDIATE` string. They went to the first keyword in the text, which in a query with WITH clauses or window functions is the wrong one (the database refused `SQL_CALC_FOUND_ROWS` there, and a sort landed inside a window function). A query with a LIMIT of its own and no `%%LIMIT%%` placeholder shows what it returns as one page instead of a broken LIMIT.
* When the report's query fails, the screen says so and shows the database's message to users who may edit reports; a query that returns no rows on a report without field definitions says that, instead of "Missing columns definition". The REST data route returns the message too.
* The report page prints a note with a spinner while a slow query runs.
* The page sizes are offered below the table as well as above it, and the table box no longer shrinks beside the search box.

= 1.1.2 =
* An export link from before 1.1.0 (the action and the format in the page's address), opened again from the history or a bookmark, no longer writes another file: the report shows with a note, and the Export buttons start a new one.
* On a wide report the bars with the export buttons and the paging stay in place while the table scrolls sideways under them.

= 1.1.1 =
* The Settings page stores the export formats that are turned off rather than the ones that are on, so a format a later version adds is on everywhere until someone turns it off. The list 1.1.0 saved is converted on upgrade.

= 1.1.0 =
* The export formats offered can be limited: for the whole site on the Settings page, and per report in its settings (a report narrows the site's list, it cannot widen it). A format that is not offered is refused by the export and by the REST route, not only hidden. CSV, TSV and TXT are written row by row; the other formats hold the whole report in memory, which matters for very large reports.
* The export buttons post a form, and the screen's script runs the export in the background: the page's address never changes, so going back or reloading never writes another file, and the note with the spinner shows at once. The form names the report outright (the group page's default report went unnamed before) and carries the search term and the sort order, so an export holds what the page shows, every row of it.
* A report with more than ten columns lays its columns out by their content and scrolls sideways in its box instead of squeezing them into the window; long values end in an ellipsis.
* The .htaccess and index.php that keep the export directory from being served are rewritten when the plugin's rules change, on upgrade and with each export; a file the site wrote itself is left alone.
* On the Settings page the descriptions sit below their buttons.

= 1.0.2 =
* A report may be several statements: session variables set before the query, a dynamic statement prepared with `EXECUTE IMMEDIATE` (`SET @day = CURRENT_DATE(); EXECUTE IMMEDIATE CONCAT( ... )`). They run one at a time on the same connection, and the paging, the search and the filters go into the last one, the query that returns the rows. Such a report no longer stops with a syntax error at its second statement.
* The row count above the list no longer shows 0 after a CSV, TSV or TXT export.
* The page count in the list's paging carries thousands separators, like the row count.
* The list shows a note while an export is being written.

= 1.0.1 =
* The plugin zip is attached to the GitHub release again: the release workflow builds it on GitHub into a draft release and publishes the release once the file is on it (1.0.0 was published before its zip could be attached).
* The menu icons and the reorder screen's drag handle are Dashicons; the PNG icons and the page icon of the old admin design, which WordPress has hidden since 3.8, are gone.
* The report list's search form compares its values with the page's query string through `URLSearchParams`, so a search that repeats the current values keeps its page and one that changes them starts on page 1.

= 1.0.0 =
* The capabilities (`exports_reports_full_access`, `exports_reports_settings`, `exports_reports_view`) sit on the administrator role: added on activation and on this upgrade, each site of a network on its own, and removed on uninstall. The filter that granted administrators full access on every request is gone (with the `exports_reports_full_access` filter it ran), so a site can take the capabilities away from a role. A network activation installs every site; a site added later installs on its first admin request.
* The hooks of the report screen pass their arguments one by one, the screen last (`exports_reports_get_data_sql( $sql, $full, $admin )`, ...), instead of one array; the `wp_admin_ui_*` names keep the array, deprecated. `exports_reports_row_actions` receives the links by action and the row; `exports_reports_limit` is a filter.
* The report list is a `WP_List_Table`: sortable column headers with their indicators, the row actions and the responsive toggle core's lists have; the search form wraps the table, so a new search starts on page 1.
* The code is in classes by task: `Exports_Reports_Query` builds the report query and its count query, the export formats are classes under `includes/exporters/`, the screens' markup is under `includes/templates/`, and `Exports_Reports_Admin_UI` handles the request. Code that called `export_sv()`, `can_stream_export()`, `pdf_engine()`, `quote()`, `rows()` or `execute()` on the screen keeps working.
* The stored version is the version itself, compared with `version_compare()`. The schema is written the way `dbDelta()` reads it, so an upgrade compares the columns instead of failing a `CREATE TABLE`.
* An export that writes nothing (a declined query) no longer fires `exports_reports_post_export` or logs an empty file name.
* Tests: a PHPUnit suite on the WordPress test library replaces the WP-CLI script, run in CI on WordPress 5.7 and 5.8 and the current release, on MariaDB and MySQL.

The releases before 1.0.0 are in [changelog.txt](https://github.com/sc0ttkclark/exports-and-reports/blob/main/changelog.txt).

== Upgrade Notice ==

= 1.3.0 =
On a network, only super admins edit groups and reports, unless a filter hands that back to the sites. Reports call only the Display functions the site lists; the ones in use are listed for you on the update. A typed API token needs 24 characters.

= 1.2.9 =
Security release: XLSX exports no longer write stored text as formulas, PDF exports on PHP below 8.2 print stored values as text, and the report page shows stored HTML as text. A column whose stored values are markup of your own is opted back in with the `exports_reports_html_values` filter.

= 1.2.0 =
The API token is now stored as a hash and cannot be shown again; the token you have keeps working. Row counts are kept for five minutes by default (Settings: Row Count Cache).

= 1.0.0 =
Administrators keep their access through capabilities placed on their role during the upgrade. Code hooked to the report screen's `exports_reports_*` filters of 0.9.5 must take positional arguments; callbacks on the `wp_admin_ui_*` names keep working.
