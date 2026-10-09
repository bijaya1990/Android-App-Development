#!/bin/sh
# Builds the installable ZIP files into dist/.
set -e
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/pikacart-core.zip dist/pikacart-theme.zip
zip -rq dist/pikacart-core.zip pikacart-core -x '*.DS_Store' '*/node_modules/*'
zip -rq dist/pikacart-theme.zip pikacart -x '*.DS_Store'
ls -lh dist
