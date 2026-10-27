#!/usr/bin/env bash
# Instalasi Backup Master di Ubuntu Server 24.04 / Debian 12-13 (jalankan sebagai root).
#
#   curl -fsSL https://raw.githubusercontent.com/andieawan/backup-master/main/deploy/install.sh -o install.sh
#   sudo LOKASI="Utama (Proxmox)" bash install.sh
#
# Variabel opsional (awali perintah dengan NAMA=nilai):
#   LOKASI       nama tempat ini: "Utama (Proxmox)", "Komputer TU", "VPS"   (tampil di layar & notifikasi)
#   DATA_DIR     folder penyimpanan backup (default /srv/backup-master) — taruh di disk besar
#   IZINKAN      jaringan yang boleh membuka halaman web (default jaringan lokal: 10/8 172.16/12 192.168/16)
#   APP_DIR      folder aplikasi (default /var/www/backup-master)
#   ADMIN        username admin pertama (default admin)
set -euo pipefail

LOKASI="${LOKASI:-Backup Master}"
DATA_DIR="${DATA_DIR:-/srv/backup-master}"
IZINKAN="${IZINKAN:-10.0.0.0/8 172.16.0.0/12 192.168.0.0/16}"
APP_DIR="${APP_DIR:-/var/www/backup-master}"
REPO="${REPO:-https://github.com/andieawan/backup-master.git}"
ADMIN="${ADMIN:-admin}"
DB_NAME=backup_master
DB_USER=backup_master
UJI_USER=backup_master_uji   # hanya untuk uji pulih & Jelajah Data: berhak atas bmj_* saja
KUNCI_DIR=/var/lib/backup-master/.ssh

info() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
gagal() { printf '\n\033[1;31mGAGAL: %s\033[0m\n' "$*" >&2; exit 1; }
sandi() { openssl rand -hex $(( ${1:-20} / 2 )); }

[ "$(id -u)" -eq 0 ] || gagal "Jalankan sebagai root (sudo bash install.sh)."
command -v apt-get >/dev/null || gagal "Skrip ini untuk Debian/Ubuntu (butuh apt)."
export DEBIAN_FRONTEND=noninteractive
IP="$(hostname -I 2>/dev/null | awk '{print $1}')"

info "Memasang paket"
apt-get update -y
apt-get install -y nginx git unzip curl openssl ca-certificates composer rsync openssh-client gzip tar cron \
    php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-bcmath php-intl mariadb-server mariadb-client
php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || gagal "Butuh PHP 8.3+ (Ubuntu 24.04 / Debian 13)."
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
SOCK="/run/php/php${PHPV}-fpm.sock"
# Unduhan backup besar & uji pulih lama: longgarkan batas waktu PHP-FPM.
cat > "/etc/php/${PHPV}/fpm/conf.d/99-backup-master.ini" <<INI
expose_php = Off
max_execution_time = 600
memory_limit = 512M
INI
systemctl enable --now "php${PHPV}-fpm" mariadb
systemctl restart "php${PHPV}-fpm"

info "Mengambil kode"
if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" pull --ff-only
else
    [ ! -e "$APP_DIR" ] || [ -z "$(ls -A "$APP_DIR")" ] || gagal "$APP_DIR sudah ada dan bukan repo git."
    mkdir -p "$(dirname "$APP_DIR")"
    git clone "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/private bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

info "Folder data $DATA_DIR dan kunci SSH"
mkdir -p "$DATA_DIR" "$KUNCI_DIR"
chown www-data:www-data "$DATA_DIR" /var/lib/backup-master "$KUNCI_DIR"
chmod 750 "$DATA_DIR" /var/lib/backup-master
chmod 700 "$KUNCI_DIR"
if [ ! -f "$KUNCI_DIR/id_ed25519" ]; then
    runuser -u www-data -- ssh-keygen -q -t ed25519 -N '' -C "backup-master@$(hostname)" -f "$KUNCI_DIR/id_ed25519"
fi
touch "$KUNCI_DIR/known_hosts"; chown www-data:www-data "$KUNCI_DIR/known_hosts"; chmod 600 "$KUNCI_DIR/known_hosts"

NEW=0
if [ ! -f .env ]; then
    NEW=1
    DB_PASS="$(sandi 24)"
    UJI_PASS="$(sandi 24)"
    info "Membuat database & dua user: $DB_USER (aplikasi) dan $UJI_USER (uji pulih, hanya bmj_*)"
    mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
CREATE USER IF NOT EXISTS '$UJI_USER'@'localhost' IDENTIFIED BY '$UJI_PASS';
CREATE USER IF NOT EXISTS '$UJI_USER'@'127.0.0.1' IDENTIFIED BY '$UJI_PASS';
ALTER USER '$UJI_USER'@'localhost' IDENTIFIED BY '$UJI_PASS';
ALTER USER '$UJI_USER'@'127.0.0.1' IDENTIFIED BY '$UJI_PASS';
GRANT ALL PRIVILEGES ON \`bmj\\_%\`.* TO '$UJI_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`bmj\\_%\`.* TO '$UJI_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
    cp .env.example .env
    sed -i -e "s|^APP_URL=.*|APP_URL=http://${IP:-localhost}|" \
        -e "s|^DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" -e "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USER|" -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" \
        -e "s|^BM_LOKASI=.*|BM_LOKASI=\"$LOKASI\"|" -e "s|^BM_DATA_DIR=.*|BM_DATA_DIR=$DATA_DIR|" \
        -e "s|^BM_UJI_DB_USERNAME=.*|BM_UJI_DB_USERNAME=$UJI_USER|" -e "s|^BM_UJI_DB_PASSWORD=.*|BM_UJI_DB_PASSWORD=$UJI_PASS|" .env
    php artisan key:generate --force
    chown root:www-data .env; chmod 640 .env
fi

artisan() { runuser -u www-data -- php "$APP_DIR/artisan" "$@"; }
artisan migrate --force
artisan config:cache; artisan route:cache; artisan view:cache

info "Nginx (hanya untuk jaringan: $IZINKAN)"
ATURAN=""; for n in $IZINKAN; do ATURAN="$ATURAN    allow $n;\n"; done
cat > /etc/nginx/sites-available/backup-master <<CONF
server {
    listen 80 default_server;
    server_name _;
    root $APP_DIR/public;
    index index.php;
    charset utf-8;
    client_max_body_size 2M;

    # Backup Master berisi data semua aplikasi: JANGAN dibuka ke internet. Akses dari luar lewat VPN/SSH tunnel.
$(printf "$ATURAN")    allow 127.0.0.1;
    deny all;

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options DENY always;
    add_header Referrer-Policy no-referrer always;
    server_tokens off;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ ^/index\.php(/|\$) {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$SOCK;
        fastcgi_read_timeout 600;
    }
    location ~ \.php\$ { return 404; }
    location ~ /\.(?!well-known).* { deny all; }
}
CONF
ln -sf /etc/nginx/sites-available/backup-master /etc/nginx/sites-enabled/backup-master
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl enable --now nginx && systemctl reload nginx

echo "* * * * * www-data cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/backup-master
chmod 644 /etc/cron.d/backup-master
systemctl enable --now cron >/dev/null 2>&1 || true

if [ "$NEW" = "1" ]; then
    ADMIN_PASS="$(sandi 16)"
    artisan bm:buat-admin "$ADMIN" --password="$ADMIN_PASS" >/dev/null
fi
artisan bm:periksa --cepat || true

info "Selesai"
echo "  Buka     : http://${IP}/   (hanya dari jaringan: $IZINKAN)"
echo "  Data     : $DATA_DIR"
[ "$NEW" = "1" ] && echo "  Admin    : $ADMIN / $ADMIN_PASS   ← catat sekarang; saat login pertama Anda diminta mengatur verifikasi dua langkah"
echo "  Kunci SSH: $(cat "$KUNCI_DIR/id_ed25519.pub")"
echo
echo "  Berikutnya: menu Sumber → tambah sumber → jalankan perintah siapkan-sumber.sh yang ditampilkan di VM sumber."
