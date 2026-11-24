# Security policy

## Supported versions

Fixes go into the latest release. Update to it before reporting: the problem may already be fixed.

## Reporting a vulnerability

Please do not open a public issue for a security problem.

Report it privately through GitHub: on the repository's **Security** tab, choose **Report a vulnerability**, or go straight to <https://github.com/sc0ttkclark/exports-and-reports/security/advisories/new>.

A useful report says:

- the plugin version, and the WordPress and PHP versions;
- the role the attacker needs (none, a user a report is shared with, an administrator);
- the steps that show the problem, with the report's query and field settings when they matter;
- what the attacker gains.

We aim to answer within a week, and to tell you when a fix is released. Reporters are credited in the changelog unless they ask not to be.

## What the plugin trusts

Knowing the design saves a report that describes it:

- **Report SQL is written by administrators and runs as written.** Only users with the plugin's settings capability (`exports_reports_settings`, administrators by default) can create or edit a report, and such a user can query the site's database by design. A way for anyone *without* that capability to change a report's SQL, or to run SQL of their own, is a vulnerability.
- **The people a report is shared with supply values, never SQL.** Search terms, filter values, the sort column, the page and the page size reach the query as prepared values or checked names. A way around that is a vulnerability.
- **Report data is untrusted.** Rows may hold text that visitors or low-privileged users entered. Data that runs as script in the admin, as a formula in an export, or that makes the server fetch an address is a vulnerability.
- **Export files are private to the users who may view the report.** A way to read or delete another report's export without access to that report is a vulnerability.
