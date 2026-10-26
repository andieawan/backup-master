#!/usr/bin/env bash
# Perbarui Backup Master ke versi terbaru (jalankan sebagai root).
set -euo pipefail
APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$APP_DIR"
artisan() { runuser -u www-data -- php "$APP_DIR/artisan" "$@"; }
git pull --ff-only
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
chown -R www-data:www-data storage bootstrap/cache
artisan migrate --force
artisan config:cache; artisan route:cache; artisan view:cache
[ -f /etc/cron.d/backup-master ] || { echo "* * * * * www-data cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/backup-master; chmod 644 /etc/cron.d/backup-master; }
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
systemctl reload "php${PHPV}-fpm"
artisan bm:periksa || echo "PERHATIAN: ada masalah di atas."
echo "Selesai diperbarui: $(git log --oneline -1)"
