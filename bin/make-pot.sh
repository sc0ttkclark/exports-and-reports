#!/usr/bin/env bash
# Writes the translation template, languages/exports-and-reports.pot, from the plugin's own PHP and JS with
# WP-CLI's i18n command (the bundled libraries, the tests and the tooling are left out). WP-CLI is
# downloaded into bin/ when it is not there, pinned to one release and checked against its published
# checksum; git ignores the file.
#
#   bin/make-pot.sh                 # rewrite languages/exports-and-reports.pot
#   bin/make-pot.sh --check         # fail when the committed template is not what the sources give
#                                   # (the creation date, which changes with every run, is not compared)
set -euo pipefail
cd "$(dirname "$0")/.."

version=2.12.0
sha512=be928f6b8ca1e8dfb9d2f4b75a13aa4aee0896f8a9a0a1c45cd5d2c98605e6172e6d014dda2e27f88c98befc16c040cbb2bd1bfa121510ea5cdf5f6a30fe8832
phar=bin/wp-cli.phar
pot=languages/exports-and-reports.pot

if [ ! -f "$phar" ]; then
	curl -sSL -o "$phar" "https://github.com/wp-cli/wp-cli/releases/download/v${version}/wp-cli-${version}.phar"
fi
if [ "$(php -r "echo hash_file( 'sha512', '$phar' );")" != "$sha512" ]; then
	echo "$phar is not WP-CLI $version; delete it and run the script again" >&2
	exit 1
fi

make_pot() {
	php "$phar" --allow-root i18n make-pot . "$1" \
		--domain=exports-and-reports \
		--exclude=vendor,tests,node_modules,bin,build,languages,.github,.wordpress-org \
		--headers='{"Report-Msgid-Bugs-To":"https://github.com/Element26-Inc/exports-and-reports/issues"}' \
		--skip-audit
}

if [ "${1:-}" = "--check" ]; then
	fresh="$(mktemp)"
	trap 'rm -f "$fresh"' EXIT
	make_pot "$fresh" >/dev/null
	if ! diff -u <(grep -v '^"POT-Creation-Date:' "$pot") <(grep -v '^"POT-Creation-Date:' "$fresh"); then
		echo "::error::$pot is out of date; run bin/make-pot.sh and commit the result" >&2
		exit 1
	fi
	echo "$pot is current"
else
	mkdir -p languages
	make_pot "$pot"
fi
