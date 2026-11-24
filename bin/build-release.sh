#!/usr/bin/env bash
# Build the distributable zip: dist/birthday-widget-for-buddypress-<version>.zip (folder = WordPress.org slug), from `git archive HEAD`
# in a scratch copy (commit first: uncommitted edits are not in the zip).
# .distignore is applied root-anchored here, never as rsync excludes: an unanchored name would
# also strip nested folders, and macOS openrsync ignores "/path" anchors.
set -euo pipefail

cd "$(dirname "$0")/.."
ROOT="$PWD"
slug=birthday-widget-for-buddypress   # WordPress.org slug = zip folder. Locked by CI (wporg-folder-lock.yml).
SLUG="$slug"
main=buddypress-birthdays.php
VERSION=$(grep -m1 -E '^\s*\*?\s*Version:' "$main" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
[ -n "$VERSION" ] || { echo "Could not read Version from the plugin header"; exit 1; }
echo "Building $SLUG v$VERSION from HEAD $(git rev-parse --short HEAD)"
[ -z "$(git status --porcelain -- . ':!dist')" ] || echo "WARNING: uncommitted changes are NOT in this zip"

SCRATCH=$(mktemp -d "${TMPDIR:-/tmp}/bpbirthdays-build.XXXXXX")
trap 'rm -rf "$SCRATCH"' EXIT
STAGE="$SCRATCH/$SLUG"
mkdir -p "$STAGE"

git archive HEAD | tar -x -C "$STAGE"
git ls-files > "$SCRATCH/tracked.txt"

# Production PHP deps, in the scratch copy only (composer.json is distignored afterwards).
if grep -q '"require"' composer.json && command -v composer >/dev/null; then
	( cd "$STAGE" && composer install --no-dev --optimize-autoloader --quiet )
fi

# Apply .distignore: glob patterns match a file name at any depth; plain names are root-anchored paths.
while IFS= read -r line; do
	line="${line%%#*}"; line="${line//[$'\t\r ']/}"; line="${line%/}"
	[ -z "$line" ] && continue
	if [[ "$line" == *[\*\?\[]* ]]; then
		find "$STAGE" -name "$line" -prune -exec rm -rf {} +
	else
		rm -rf "${STAGE:?}/$line"
	fi
done <.distignore

# Every tracked file that .distignore does not exclude must be in the zip, plus the named runtime files.
python3 - "$SCRATCH/tracked.txt" "$STAGE" .distignore <<'PY'
import sys, os, fnmatch
tracked, stage, ign = sys.argv[1:4]
pats = [l.split('#', 1)[0].strip().rstrip('/') for l in open(ign)]
pats = [p for p in pats if p]
def excluded(p):
    parts = p.split('/')
    for pat in pats:
        if any(c in pat for c in '*?['):
            if any(fnmatch.fnmatch(x, pat) for x in parts):
                return True
        elif p == pat or p.startswith(pat + '/'):
            return True
    return False
files = [p for p in open(tracked).read().split('\n') if p]
missing = [p for p in files if not excluded(p) and not os.path.exists(os.path.join(stage, p))]
for must in ('buddypress-birthdays.php', 'core-init.php', 'uninstall.php', 'assets/js/bb-core.min.js', 'assets/css/bb-core.min.css',
             'assets/css/admin.css', 'assets/js/admin.js', 'assets/inc/buddypress-birthdays-widget.php',
             'includes/class-bp-birthdays-helpers.php', 'admin/class-bp-birthdays-admin.php', 'readme.txt'):
    if not os.path.exists(os.path.join(stage, must)):
        missing.append(must)
if missing:
    print('BUILD FAILED: files missing from the release tree:')
    for m in missing[:40]:
        print('  ' + m)
    sys.exit(1)
print('assertions ok: %d tracked files accounted for' % sum(1 for p in files if not excluded(p)))
PY

mkdir -p dist
rm -f "dist/${SLUG}-${VERSION}.zip"
# Deflate every entry: a PHP file stored uncompressed trips the store's upload firewall.
( cd "$SCRATCH" && python3 -I -c 'import os,sys,zipfile
out,top=sys.argv[1],sys.argv[2]
with zipfile.ZipFile(out,"w",zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for r,d,f in os.walk(top):
        d.sort()
        for n in sorted(f): z.write(os.path.join(r,n))' "$ROOT/dist/${SLUG}-${VERSION}.zip" "$SLUG" )
zip="$ROOT/dist/${SLUG}-${VERSION}.zip"
# --- WordPress.org folder lock (Wbcom standard - do not edit) ----------------------
entries=$(unzip -Z1 "$zip")   # read the whole list once: no early-exit pipe, safe under pipefail
top=${entries%%/*}
[ "$top" = "$slug" ] || { echo "Zip folder '$top' is not the WordPress.org slug '$slug'"; exit 1; }
grep -qx "$slug/$main" <<<"$entries" || { echo "Main file $slug/$main missing from zip"; exit 1; }
[ "$(LC_ALL=C grep -a -c '<?php' "$zip" || true)" = 0 ] || { echo "Zip has plain-text PHP (upload firewall)"; exit 1; }
# -----------------------------------------------------------------------------------
echo "Created dist/${SLUG}-${VERSION}.zip"
