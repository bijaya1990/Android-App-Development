#!/bin/sh
# Builds dist/su-id-card-generator.zip from the plugin source and the app in
# ../id-card-generator. Run from anywhere: sh wordpress-plugin/build.sh
set -e
HERE=$(cd "$(dirname "$0")" && pwd)
APP="$HERE/../id-card-generator"
OUT="$HERE/dist"
TMP=$(mktemp -d)
cp -r "$HERE/su-id-card-generator" "$TMP/"
mkdir -p "$TMP/su-id-card-generator/app/js"
cp "$APP/index.html" "$TMP/su-id-card-generator/app/"
cp -r "$APP/lib" "$TMP/su-id-card-generator/app/"
cp "$APP/js/fonts.js" "$TMP/su-id-card-generator/app/js/"
mkdir -p "$OUT"
rm -f "$OUT/su-id-card-generator.zip"
(cd "$TMP" && zip -qr -X "$OUT/su-id-card-generator.zip" su-id-card-generator)
rm -rf "$TMP"
echo "Built $OUT/su-id-card-generator.zip"
