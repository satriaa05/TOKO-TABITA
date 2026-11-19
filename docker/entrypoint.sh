#!/bin/sh
# Entrypoint produksi untuk Render (dipakai lewat Dockerfile CMD).
# Menyiapkan port nginx (Render mengeset $PORT) + database, lalu menjalankan supervisor.
set -e

# Nginx perlu dengar di port yang dipakai Render.
PORT_NUM="${PORT:-80}"
sed -i "s/listen 80;/listen ${PORT_NUM};/" /etc/nginx/sites-available/default

# APP_KEY wajib ada; produksi gagal dengan pesan jelas bila kosong.
if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY belum diisi. Isi di Render Dashboard > Environment > Environment Variables (php artisan key:generate --show)."
    exit 1
fi

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan view:clear

# Data esensial WAJIB ada di produksi (metode bayar + permission).
# Idempoten, aman dijalankan setiap deploy.
php artisan db:seed --class=PaymentChannelSeeder --force
php artisan db:seed --class=PermissionSeeder --force

# Seed data demo (produk/akun contoh) hanya bila SEED_DEMO=true.
if [ "${SEED_DEMO:-false}" = "true" ]; then
    php artisan db:seed --class=DatabaseSeeder --force
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
