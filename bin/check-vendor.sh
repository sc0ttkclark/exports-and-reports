#!/usr/bin/env bash
# Proves that the committed vendor tree is what composer.lock and Strauss produce:
#   1. composer.json and composer.lock agree;
#   2. the committed vendor/composer/installed.json lists the lock's runtime packages at the locked versions
#      (the originals of the prefixed packages are not committed, only their prefixed copies, so a dry run
#      of composer install cannot be the comparison);
#   3. vendor/vendor-prefixed and vendor/composer/autoload_aliases.php are what the pinned Strauss makes of
#      a fresh --no-dev install of the lock: a run changes none of them (the installed.json and
#      installed.php files record how the packages were fetched, dist or source, and are left out).
# Needs network. Leaves the installed originals and bin/strauss.phar behind, both ignored by git.
set -euo pipefail
cd "$(dirname "$0")/.."
export COMPOSER_NO_INTERACTION=1

composer validate --strict --no-check-publish

php -r '
$lock = json_decode( (string) file_get_contents( "composer.lock" ), true );
$have = json_decode( (string) file_get_contents( "vendor/composer/installed.json" ), true );
$want = array();
foreach ( $lock["packages"] as $p ) { $want[ $p["name"] ] = $p["version"]; }
$got = array();
foreach ( ( $have["packages"] ?? $have ) as $p ) { $got[ $p["name"] ] = $p["version"]; }
ksort( $want ); ksort( $got );
if ( $want !== $got ) {
	echo "composer.lock (runtime):\n"; foreach ( $want as $n => $v ) { echo "  $n $v\n"; }
	echo "vendor/composer/installed.json:\n"; foreach ( $got as $n => $v ) { echo "  $n $v\n"; }
	fwrite( STDERR, "::error::vendor/composer/installed.json is not the lock; run composer install --no-dev and commit vendor/composer\n" );
	exit( 1 );
}
echo "vendor/composer/installed.json lists the " . count( $got ) . " locked runtime packages\n";
'

# A fresh install of the originals: Strauss deletes their files after copying (delete_vendor_files), and
# Composer takes a directory that still exists for an installed package, so a second run on the same
# tree would have nothing to copy. The committed tree holds only vendor/composer, vendor/autoload.php
# and vendor/vendor-prefixed.
find vendor -mindepth 1 -maxdepth 1 ! -name composer ! -name vendor-prefixed ! -name autoload.php -exec rm -rf {} +
composer install --no-dev --no-scripts
composer run-script strauss

drift="$(git status --porcelain -- vendor/vendor-prefixed vendor/composer/autoload_aliases.php | grep -v -E 'composer/installed\.(json|php)$' || true)"
if [ -n "$drift" ]; then
	echo "$drift" | head -n 50
	echo "::error::vendor/vendor-prefixed is not what Strauss produces from the locked packages; run composer run-script strauss and commit the result"
	exit 1
fi

echo "vendor ok: the lock matches composer.json, vendor/composer matches the lock, the prefixed copy is current"
