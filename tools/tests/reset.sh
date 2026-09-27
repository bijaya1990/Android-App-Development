#!/bin/bash
cd /tmp/claude-0/-home-user-Android-App-Development/5e013f51-1b71-561e-ab32-a9557c2bd811/scratchpad/wordpress
rm -rf wp-content/database wp-content/uploads /tmp/claude-0/-home-user-Android-App-Development/5e013f51-1b71-561e-ab32-a9557c2bd811/scratchpad/mail.log wp-content/debug.log
WP="php /tmp/claude-0/-home-user-Android-App-Development/5e013f51-1b71-561e-ab32-a9557c2bd811/scratchpad/wp-cli.phar --allow-root --path=/tmp/claude-0/-home-user-Android-App-Development/5e013f51-1b71-561e-ab32-a9557c2bd811/scratchpad/wordpress"
$WP core install --url=http://localhost:8899 --title="DigiMarket Test" --admin_user=admin --admin_password=admin123 --admin_email=admin@example.com --skip-email >/dev/null 2>&1
$WP option update home http://localhost:8899 >/dev/null; $WP option update siteurl http://localhost:8899 >/dev/null
$WP plugin activate sqlite-database-integration >/dev/null 2>&1
$WP theme activate digimarket 2>&1 | tail -1
$WP rewrite flush >/dev/null 2>&1
if [ -z "$DEMO" ]; then
# Legacy categories used by the e2e suites.
for c in Ebooks Courses Templates Software Music Graphics Presets Fonts "Design Assets" "Plugins & Code"; do $WP term create dm_category "$c" >/dev/null 2>&1; done
fi
