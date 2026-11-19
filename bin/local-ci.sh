#!/usr/bin/env bash
# Local CI gate: run before every push. Q1 PHP lint, Q2 version triangulation, Q3 changelog,
# Q4 time standard, Q5 committed .min files match their sources, Q6 PHPStan (level 5).
set -uo pipefail

cd "$(dirname "$0")/.."
FAIL=0
section() { printf '\n\033[1m== %s ==\033[0m\n' "$1"; }

section "Q1 - PHP lint"
if find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 -n1 -P4 php -l >/dev/null; then
	echo "ok"
else
	echo "FAIL: PHP syntax errors"; FAIL=1
fi

section "Q2 - version triangulation"
HEADER_V=$(grep -m1 -E '^\s*\*?\s*Version:' buddypress-birthdays.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
CONST_V=$(grep -m1 -oE "BIRTHDAY_WIDGET_VERSION'?,?\s*'?[0-9]+\.[0-9]+\.[0-9]+" buddypress-birthdays.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
README_V=$(grep -m1 -E '^Stable tag:' readme.txt | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
PKG_V=$(grep -m1 '"version"' package.json | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
echo "header=$HEADER_V const=$CONST_V readme=$README_V package=$PKG_V"
if [ -n "$HEADER_V" ] && [ "$HEADER_V" = "$CONST_V" ] && [ "$HEADER_V" = "$README_V" ] && [ "$HEADER_V" = "$PKG_V" ]; then echo "ok"; else echo "FAIL: version mismatch"; FAIL=1; fi

section "Q3 - changelog entry"
if grep -qE "^= ${HEADER_V} - " readme.txt; then echo "ok"; else echo "FAIL: no '= ${HEADER_V} - Month Year =' in readme.txt"; FAIL=1; fi
if grep -q '—' readme.txt; then echo "FAIL: em dash in readme.txt"; FAIL=1; fi

section "Q4 - time standard"
php bin/time-standard-check.php . || FAIL=1

section "Q5 - committed .min files match their sources"
if [ -d node_modules ]; then
	snap() { git ls-files -z -- assets | xargs -0 shasum | shasum; }
	before=$(snap)
	npx grunt minify >/dev/null 2>&1 || { echo "FAIL: grunt minify failed"; FAIL=1; }
	if [ "$before" = "$(snap)" ]; then echo "ok"; else echo "FAIL: run npx grunt minify and commit the .min files"; git status --short -- assets; FAIL=1; fi
else
	echo "skip: run npm install"
fi

section "Q6 - PHPStan (level 5)"
if [ -x vendor/bin/phpstan ]; then
	vendor/bin/phpstan analyse --no-progress --memory-limit=1G -c phpstan.neon || FAIL=1
else
	echo "skip: run composer install"
fi

section "Result"
[ "$FAIL" -eq 0 ] && echo "PASS" || { echo "FAIL"; exit 1; }
