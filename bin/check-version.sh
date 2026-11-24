#!/usr/bin/env bash
# Fails unless every place the plugin declares its version carries the one given (a release tag,
# with or without its leading v): the plugin header, readme.txt's stable tag, package.json, and the
# EXPORTS_REPORTS_VERSION constant the upgrade routine compares.
set -euo pipefail
cd "$(dirname "$0")/.."

expected="${1#v}"
if [ -z "$expected" ]; then
	echo "usage: $0 <version|tag>" >&2
	exit 2
fi

header="$(sed -n 's/^ \* Version:[[:space:]]*//p' exports-and-reports.php | head -n1 | tr -d '[:space:]')"
stable="$(sed -n 's/^Stable tag:[[:space:]]*//p' readme.txt | head -n1 | tr -d '[:space:]')"
package="$(jq -r .version package.json)"
constant="$(sed -n "s/^define( 'EXPORTS_REPORTS_VERSION', '\([0-9.]*\)' );.*/\1/p" exports-and-reports.php | head -n1)"

status=0
check() {
	if [ "$2" = "$3" ]; then
		echo "ok    $1: $2"
	else
		echo "wrong $1: $2 (expected $3)"
		status=1
	fi
}
check "exports-and-reports.php Version header" "$header" "$expected"
check "readme.txt Stable tag" "$stable" "$expected"
check "package.json version" "$package" "$expected"
check "EXPORTS_REPORTS_VERSION constant" "$constant" "$expected"
exit $status
