=== Exports and Reports ===
Contributors: sc0ttkclark
Donate link: https://www.scottkclark.com/
Tags: exports, reports, reporting, exporting, csv, xlsx, pdf, xml, json
Requires at least: 3.8
Tested up to: 5.0
Stable tag: 0.8.1

Define custom exports / reports for users, based off of any custom MySQL SELECT query you define.

== Description ==

Define custom exports / reports for users, based off of any MySQL SELECT query you create. This plugin interacts with your SELECT query and does all the hard work for you: exporting, pagination, ordering, searching/filtering, and display formatting for you.

All you do is install the plugin, create your Groups, create your Reports, and hand it off to your clients. Exportable reports in CSV, TSV, XML, JSON, and custom delimiter separated formats.

Please submit bug reports or contribute your own enhancements/fixes on [GitHub](https://github.com/sc0ttkclark/exports-and-reports).

== Frequently Asked Questions ==

**What are Groups?**

Groups are groupings of Reports that are given their own menu item in the "Reports" menu.

**What is a Report?**

A Report is defined by a Custom MySQL query and can be configured to display however you wish using additional field definitions. Exports can be disabled per report.

**My report isn't working**

As an admin, add &debug=1 to the end of the report URL to see the query that this plugin uses, take that query and use it in your own MySQL client or PHPMyAdmin to see if there are any errors in your own query.

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Features ==

= Administration =
* Create and Manage Groups
* Create and Manage Reports
* Limit which User Roles have access to a Group or Report
* Ability to clear entire export directory (based on logged export files)
* Daily Export Cleanup via wp_cron
* WP Admin UI - A class for plugins to manage data using the WordPress UI appearance

= Reporting =
* Filter by Date
* Automatic Pagination
* Show only the fields you want to show
* Pre-display modification through custom defined function per field or row

= Exporting =
* CSV - Comma-separated Values (w/ Excel support)
* TSV - Tab-separated Values (w/ Excel support)
* TXT - Pipe-separated Values (w/ Excel support)
* XLSX - Excel format, using [PHP_XLSXWriter](https://github.com/mk-j/PHP_XLSXWriter)
* XML - XML 1.0 UTF-8 data
* JSON - JSON format
* PDF - PDF printer friendly views, using [TCPDF](https://tcpdf.org/)
* Custom - Custom delimiter separated Values (Update the report screen URL parameters to `&action=export&export_type=custom&export_delimiter=#` and change # to whatever delimiter you want)

= Cronjob / JSON API =
* Run the Export action for a specific report to any supported export type
* Get paginated / full data from a report in JSON format

== Changelog ==

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