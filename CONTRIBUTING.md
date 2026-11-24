# Contributing

Bug reports and pull requests are welcome in the [issues](https://github.com/sc0ttkclark/exports-and-reports/issues) and [pull requests](https://github.com/sc0ttkclark/exports-and-reports/pulls) of this repository. A security problem is reported privately instead: see [`SECURITY.md`](SECURITY.md).

## Reporting a bug

Say which version of the plugin, of WordPress and of PHP the site runs, and include the report's query, its field settings and what you expected to see. A report page opened with `&debug=1` by an administrator shows the query the plugin built, which usually settles where the problem is.

## Pull requests

- **One change per pull request.** A fix and an unrelated cleanup are two pull requests.
- **A test for a change in behavior.** The suite in `tests/phpunit/` runs the plugin against a real database; [`tests/README.md`](tests/README.md) says how to run it and what each class covers. A bug fix starts with the test that fails without it.
- **The readme changes with the code.** `readme.txt` is the user documentation and the wordpress.org listing: add a line to the changelog section of the next version, and update the FAQ or the hook list when an answer there changes. A new filter, action, constant or setting is documented in the same pull request.
- **Strings are translatable.** Run `bin/make-pot.sh` when a string is added or changed, and commit the template.
- **The checks pass.** The README lists the commands; they all run on the pull request.
- **Bundled libraries change through Composer.** `vendor/` is committed and checked against `composer.lock` (`bin/check-vendor.sh`); do not edit a file under `vendor/` by hand.
- **Commit messages are one line** that says what is true after the commit, for example "The search skips date and time fields".

## What the plugin supports

WordPress 5.7 and PHP 7.2 are the floors: code the plugin loads on PHP 7.2 must parse and run there. The tc-lib-pdf engine is the exception, and loads on PHP 8.2 and later only.

Stored report definitions and the documented hooks keep working across releases. A change that alters what an existing report shows or exports comes with a filter that restores the old behavior, and a line in the changelog that says so.
