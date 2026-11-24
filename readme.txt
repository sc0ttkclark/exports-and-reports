=== Exports and Reports ===
Contributors: sc0ttkclark, dxdc
Donate link: https://github.com/sponsors/dxdc
Tags: export, reports, csv, excel, sql
Requires at least: 5.7
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.2.9
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

= Can a report run more than one statement? =

Yes. End each with a semicolon: session variables first (`SET @day = CURRENT_DATE();`), a dynamic statement if the report builds one (`EXECUTE IMMEDIATE CONCAT( ... );`), and last the query that returns the rows. They run in order on one connection, so the last statement reads what the ones before it set up. The plugin's paging, search and filters go into the last statement only.

= How do scripts and cronjobs read a report? =

Through the REST routes under `/wp-json/exports-reports/v1/reports/ID/` (the Settings page shows them), as a logged-in user who may view the report (cookie and nonce, or an application password) or with the API token from the Settings page, sent as the header `Authorization: Bearer TOKEN` or as a `token` query parameter; the header keeps the token out of server logs. The token is stored as a hash, like a password, so it is shown once, when you generate it or set one of your own; generate a new one if it is lost, or turn token access off. A fresh install has token access off.

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
* Exports: `exports_reports_export_file( $file, $type, $extension, $items, $admin )` for the file name; `exports_reports_export_escape_formulas( $escape, $admin )` for the CSV, TSV, TXT and XLSX exports; `exports_reports_export_pdf_engine( $engine )` and `exports_reports_export_pdf_report_file( $file, $admin )`; the actions `exports_reports_pre_export( $admin )`, `exports_reports_post_export( $file, $admin )`, `exports_reports_pre_remove_export( $file, $path, $admin )`, `exports_reports_post_remove_export( $file, $removed, $path, $admin )` and `exports_reports_export_download`.
* The screens: `exports_reports_report_options( $options, $report_id )` and `exports_reports_options( $options, $admin )` for a screen's settings; `exports_reports_setup_columns( $columns, $which, $init, $admin )` and `exports_reports_setup_column( $attributes, $column, $filterable, $admin )`; `exports_reports_row_actions( $links, $row, $admin )`; `exports_reports_limit( $sizes, $admin )` for the page sizes; `exports_reports_allowed_html( $allowed )` for the HTML a display callback may return and `exports_reports_html_values( $html, $column, $attributes, $admin )` to show a column's stored values as HTML instead of text; `exports_reports_display_callbacks( $callbacks )` for the functions a field definition may name as its Display function, and `exports_reports_allowed_callback( $allowed, $callback, $admin )` to decide for one name; the actions `exports_reports_pre_init( $options )`, `exports_reports_post_init( $options, $admin )`, `exports_reports_pre_save( $create, $admin )`, `exports_reports_post_save( $id, $data, $create, $admin )`, `exports_reports_pre_delete( $id, $admin )`, `exports_reports_post_delete( $id, $admin )`, `exports_reports_post_reorder( $id, $admin )`.
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

= REST API / Cronjob =
* Run the Export action for a specific report to any supported export type, and download the file
* Get paginated / full data from a report in JSON format
* Routes under /wp-json/exports-reports/v1/reports/ID/ (data, export, exports/FILE), for a logged-in user who may view the report or for the API token from the Settings page, sent as "Authorization: Bearer TOKEN" or as ?token=; the header keeps the token out of URLs and server logs; the Settings page shows the URLs. The token is stored as a hash and shown once, when it is generated

== Screenshots ==

1. View the report, export data, search content, filter by date, and paginate through the records
2. Create and manage report groups that can contain their own sets of reports
3. Create and manage reports
4. Specify the SQL query that selects the data; placeholders put the search, the filters, the sort and the page where you want them, and an optional totals query feeds the Totals row
5. Specify the fields to display on the report and what kind of data they are, or detect them from the query
6. Exports run in the background: the message with the download and the button that removes the file
7. Set advanced field options to further customize how fields will be displayed, exported, searched, filtered, or related to other tables
8. Manage plugin settings and get useful URLs for API access

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
* A field's Display function, and the helper of a `{@field,helper}` tag, is called only when the site lists it through the new `exports_reports_display_callbacks` filter; a definition could name any function before. The functions your reports use at the time of the update are listed for you, and a notice names them.
* On a network (multisite), groups and reports are created and edited by super admins only: a report's SQL reaches every site's tables. Site administrators still view the reports and keep the Settings page. `add_filter( 'exports_reports_site_admins_edit_reports', '__return_true' );` hands the editing back to the sites.

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

= 0.9.5 =
* Security: search and filter values reach the query through `$wpdb->prepare()` with LIKE wildcards escaped, and identifiers from the report definition are quoted as identifiers; the Settings page, the reorder form and the save path verify a nonce; the report field editor sanitises its inputs; the export directory is no longer served by the web server (an .htaccess and an index.php are written, downloads go through the plugin after a nonce or token check).
* Added a REST API (`exports-reports/v1/reports/{id}/data`, `/export`, `/exports/{file}`): the cronjob token as a bearer token or as the `token` parameter, or the logged-in user's access. The old `api.php` endpoint is removed; cronjobs use these routes (the Settings page shows the URLs).
* Added the `exports_reports_get_data_sql` and `exports_reports_get_data_sql_count` filters, applied to the report query and its count query right before they run.
* CSV, TSV and pipe exports stream from an unbuffered result, so memory no longer grows with the table; every value is quoted, a UTF-8 byte order mark is written, and formula-like values are wrapped as text (`exports_reports_export_escape_formulas` turns that off).
* Tabs around the `%%LIMIT%%` placeholder are handled like newlines.
* PDF exports render with tc-lib-pdf on PHP 8.2 and later (TCPDF, deprecated by its author, stays the engine below 8.2); the `exports_reports_export_pdf_engine` filter can keep TCPDF on a newer PHP.
* The plugin's tables use the site's character set and collation; existing tables are converted on upgrade.
* Added uninstall.php. The capabilities filter is `exports_reports_capabilities`; the misspelled `export_reports_capabilites` still works with a deprecation notice.
* The report form's field editor is rebuilt (one card per field with a drag handle, advanced options folded), the query fields use the WordPress code editor with SQL highlighting, and multiple-choice settings are checkbox lists.
* The daily cleanup event has its handler, is cleared on deactivation and is put back when it is missing.
* A sort column the request names must be one of the report's columns (its field definitions, or the names and aliases of its SELECT list); otherwise the report's own order applies. A report whose columns cannot be known before it runs takes the name as an identifier, which the database checks.
* Export files live in the uploads directory (`wp-content/uploads/exports-and-reports` by default); the files the site's export log names move over from the old `wp-content/exports` on upgrade. `EXPORTS_REPORTS_EXPORT_DIR` and `EXPORTS_REPORTS_EXPORT_URL` relocate it; the `WP_ADMIN_UI_` names are still read.
* The page view of a report with a count query (the totals row) counts every row again; it had stopped at the first page.
* Every hook carries the plugin's prefix: `exports_reports_` replaces `wp_admin_ui_` in the filter and action names (`exports_reports_get_data_sql`, `exports_reports_export_file`, `exports_reports_post_export`, ...), and the download link's admin-ajax action is `exports_reports_export`. The old hook names still run their callbacks, with a deprecation notice.
* The report screens escape the output of display, view and header callbacks with the post-content HTML rules (the `exports_reports_allowed_html` filter extends them), the screens' scripts are in an enqueued file with no inline handlers, and the table rows are striped by the admin styles. The window the export page opens carries the file again, and the delete links ask for confirmation again.
* The files are arranged by kind: the PHP in `includes/` (the main file keeps the constants, the hooks and the requires), the scripts, styles, images and PDF font metrics under `assets/`, the schema in `includes/schema.sql`; the `wp-admin-ui` directory is gone. Code that required one of the plugin's files by path needs the new path.
* Every string the admin screens show is translatable (text domain `exports-and-reports`), the screens' styles are in the stylesheet, and the release archive leaves the Node package metadata out.
* Requires WordPress 5.7 (4.9 before) and PHP 7.2 (down from 8.0); tested up to WordPress 7.1.

= 0.9.4 =
* Fixed PHP compatibility issues.
* Fixed compatibility issues when on sites that load their own XLSXWriter class.
* Updated XLSXWriter and TCPDF to the latest versions.

= 0.9.3 =
* Fixed issue with overriding the filtered content for a value.
* Fixed problems reported with opening CSV in certain spreadsheet applications which read some CSV values as actual formulas.

= 0.9.1 =
* Fixed issue with exporting via API. #33 #26 #25 (props @liedekef)
* Fixed reports so when they are hidden then they will no longer show in the dropdowns. #33 #25 (props @liedekef)
* Fixed access when mixing group permissions with individual report permissions. #29 #28 (props @dxdc)
* Fixed PHP notices for page orientation. #29 #27 (props @dxdc)

= 0.9.0 =
* Resolve PHP warning with TCPDF library.
* Now ensuring robots.txt blocks the exports directory from being indexing in certain circumstances.
* Updated compatibility with WP 5.9 and PHP 8.0

= 0.8.8 =
* Fixed an issue with the XLSX PHP library having deprecated notices, updated it to latest released version.

= 0.8.7 =
* Fixed a problem with backslashes in URLs for Windows file exports, props @ZelonGames [#16]
* Removed jQuery UI Datepicker functionality and replaced with HTML input types date, time, and datetime-local. [#10]
* Added support for numeric values in XLSX exports instead of treating them as strings for the export. [#18]
* Updated compatibility with WP 5.7

= 0.8.6 =
* Fixed a problem where the SQL upgrade would try to run after it's already done the install and is already up-to-date
* Updated compatibility with WP 5.5

= 0.8.5 =
* Updated compatibility with WP 5.4

= 0.8.4 =
* Fix some output escaping in the WP_Admin_UI class

= 0.8.3 =
* Fixed multiple group pages not showing the correct group view for first group in list [#8]
* Fixed export variable notices (props @rdaniel0) [#12]

= 0.8.2 =
* Added new filter `wp_admin_ui_export_pdf_report_file` to override the default `WP_Admin_UI_Export_PDF::CreateReport( $this )` process when creating PDF reports.
* Fixed PDF report titles that said 'item' instead of the report title.

= 0.8.1 =
* Moved install logic from init into activation hook, some times the plugin's version option was coming up empty and causing DB table resets.
* Implemented dbDelta function for schema modifications.

= 0.8.0 =
* Added ability to export to TXT file (pipe-delimited)
* Added ability to export to XLSX file
* Added ability to export to PDF file
* Lots of escaping improvements
* PHPCS fixes and formatting
* Other miscelaneous fixes and improvements to code quality

= 0.7.4 =
* Fix for report dropdown link problem introduced in 0.7.3 (props @andrewgosali)

= 0.7.3 =
* Added ability to set the ID field for a related table field (default was `id`, now you can customize it)
* Fix for pagination LIMIT bug (props @andrewgosali)
* Fix for is_plugin_active bug (props @skillio and @cvladan)
* Fix for get_currentuserinfo deprecation error
* Fixes for potential conflicts with other plugins that use the "export" and "download" URL parameters, they are now "exports_and_reports_export" and "exports_and_reports_download"
* Some more minor escaping fixes

= 0.7.2 =
* Fix for files not downloading completely (on some environments)
* Additional escaping fixes for WP_Admin_UI (reported by Sathish Kumar from cybersecurity works)

= 0.7.1 =
* Escaping fixes for WP_Admin_UI (reported by Sathish Kumar from cybersecurity works)

= 0.7.0 =
* Added: Using WP AJAX URL instead of Admin.class.php directly for downloads of exports

= 0.6.4 =
* Added: New constant to change the exports directory (WP_ADMIN_UI_EXPORT_DIR / WP_ADMIN_UI_EXPORT_URL)
* Added: New filter to change the exported filename (wp_admin_ui_export_file, filter is passed filename and export type)
* Added: Ability to use the API to export and then download the file (previously only JSON was available about file)
* Added: New filter to override the WP_Admin_UI options array (exports_reports_report_options, filter is passed an array of options)
* Fixed: JSON API for downloading data (full or paginated) now returns data properly, previously wasn't returning the actual data

= 0.6.3 =
* Security fix for orderby handling

= 0.6.2 =
* Added: Export JSON response now includes export file and message OR Error message from Cronjob URL (props to @adminatvbds for the idea)

= 0.6.1 =
* Fixed: How the token gets generated is more randomized now

= 0.6.0 =
* Feature: Added new Cronjob URL which you can define report or export type (requires WP 3.5+)
* Feature: Added new JSON API URL which you can define report, export type, and optional pagination (requires WP 3.5+)
* Changed: Format of exported file names is now utilizing 24 hour format, along with a random string at the end
* Fix: Tweaks to table markup

= 0.5.3 =
* Fix for exporting and URL paths on Windows, props to @fantomas_bg

= 0.5.2 =
* Bug fixes, added nonces for exports

= 0.5.1 =
* Feature: Reports menu split from Reports Admin so they're two separate menus now to avoid confusion for users
* Feature: Ability to reorder Groups so you can choose the order they appear in the menu
* Feature: Ability to set a separate query for use when getting the 'total' count (for advanced / complex queries which otherwise would be a bad idea to use SQL_CALC_FOUND_ROWS for)
* Feature: Report field settings now have 'Advanced' section that can be expanded to view advanced settings, otherwise you can use the three simple settings which are 'Field Name', 'Data Type', and 'Label (optional)'
* Feature: Now when you don't enter ANY fields in the Field Settings area, the report will pick up fields directly from the query for you when you display a report
* Feature: When making a boolean data type field filterable, a new filter will appear to choose '-- Show All --', 'Yes', and/or 'No'
* Feature: If you're having trouble with a report, you can enable debug mode by adding debug=1 to the Report URL (must be Administrator)
* Fix: Date fields that are empty (0000-00-00 00:00:00) will now show 'N/A' instead
* Fix: Related fields have better handling for SQL building
* Fix: No longer using default ORDER BY when no ORDER BY is found in SQL, causing issues with tables that didn't have a field 'id' set
* Fix: Smarter WHERE and HAVING dynamic building
* Fix: Lots of PHP warnings / notice cleanups and general code tweaks to plugin and WP_Admin_UI class

= 0.4.2 =
* Feature: Added ability to set the a field's filter default value
* Feature: Added additional 'related' field type options (where/order by SQL, related ON field)
* Feature: Added 'default_none' option to DB and Report editor, which allows you to default a report to show no results until a search / filter is done, exports clicked will continue to generate the full export before search and/or filtering
* Feature: EXPORTS_REPORTS_DISABLE_MENU constant added to disable the menu from being output completely (aside from the normal exports_reports_* capabilities you can define under user roles
* Fix: Added comments to SQL Query field in Report editor to explain advanced %%TAGS%% which can be used
* Fix: Reports list now ordered by Group then Weight (same for reordering)
* Fix: Forcing version to be int instead of text when getting version from DB
* Fix: Various minor bug fixes to plugin and WP Admin UI class

= 0.3.3 =
* Bug fix for SQL (another)

= 0.3.2 =
* Bug fix for SQL
* Moved About to bottom of Menu
* Added 'Reset' option in Settings

= 0.3.1 =
* Fixed menu access

= 0.3 =
* Upgraded Admin.class.php with Bug fixes and features (better UI and filtering)
* Export data fixes on CSV / TSV to support Excel
* Redefined Date Data Type into three (Date, Date + Time, Time)
* Filter by Date
* Ability to clear entire export directory (based on logged export files)
* Daily Export Cleanup via wp_cron

= 0.2 =
* First official release to the public as a plugin

== Upgrade Notice ==

= 1.2.9 =
Security release: XLSX exports no longer write stored text as formulas, PDF exports on PHP below 8.2 print stored values as text, and the report page shows stored HTML as text. A column whose stored values are markup of your own is opted back in with the `exports_reports_html_values` filter.

= 1.2.0 =
The API token is now stored as a hash and cannot be shown again; the token you have keeps working. Row counts are kept for five minutes by default (Settings: Row Count Cache).

= 1.0.0 =
Administrators keep their access through capabilities placed on their role during the upgrade. Code hooked to the report screen's `exports_reports_*` filters of 0.9.5 must take positional arguments; callbacks on the `wp_admin_ui_*` names keep working.
