#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════════════
# Sinemani - One-Time Server Setup Script
# Run this ONCE on a fresh Ubuntu server (22.04/24.04)
# Usage: ssh root@146.190.113.112 'bash -s' < deployment/setup-server.sh
# ═══════════════════════════════════════════════════════════════

APP_DIR="/var/www/dramabox"
REPO_URL="https://github.com/magorikihore/sinemani.git"
BRANCH="main"
DB_NAME="dramabox"
DB_USER="dramabox_user"
DB_PASS=$(openssl rand -base64 24)

echo "══════════════════════════════════════"
echo "  Sinemani Server Setup"
echo "══════════════════════════════════════"

# ── 1. System Updates ──────────────────────────────────────────
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# ── 2. Install PHP 8.3 + Extensions ───────────────────────────
echo "🐘 Installing PHP 8.3..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
    php8.3-xml php8.3-curl php8.3-gd php8.3-mbstring php8.3-zip \
    php8.3-bcmath php8.3-intl php8.3-readline php8.3-sqlite3 \
    php8.3-imagick php8.3-redis

# ── 3. Install MySQL ──────────────────────────────────────────
echo "🗄️  Installing MySQL..."
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "✅ Database created: ${DB_NAME}"
echo "✅ Database user: ${DB_USER}"
echo "✅ Database password: ${DB_PASS}"

# ── 4. Install Nginx ──────────────────────────────────────────
echo "🌐 Installing Nginx..."
apt install -y nginx
systemctl enable nginx

# ── 5. Install Composer ───────────────────────────────────────
echo "🎵 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# ── 6. Install other tools ────────────────────────────────────
apt install -y git unzip ffmpeg supervisor redis-server
systemctl enable redis-server
systemctl start redis-server

# ── 7. Clone the repository ───────────────────────────────────
echo "📂 Cloning repository..."
mkdir -p /var/www
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR" && git pull origin "$BRANCH"
else
    git clone -b "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR"

# ── 8. Install Laravel dependencies ───────────────────────────
echo "📦 Installing Laravel dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ── 9. Configure environment ──────────────────────────────────
echo "⚙️  Setting up .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate

    # Update .env for production
    sed -i "s|APP_ENV=local|APP_ENV=production|" .env
    sed -i "s|APP_DEBUG=true|APP_DEBUG=false|" .env
    sed -i "s|APP_URL=http://localhost|APP_URL=https://api.sinemani.net|" .env
    sed -i "s|DB_CONNECTION=sqlite|DB_CONNECTION=mysql|" .env
    sed -i "s|# DB_HOST=127.0.0.1|DB_HOST=127.0.0.1|" .env
    sed -i "s|# DB_PORT=3306|DB_PORT=3306|" .env
    sed -i "s|# DB_DATABASE=laravel|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|# DB_USERNAME=root|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|# DB_PASSWORD=|DB_PASSWORD=${DB_PASS}|" .env
fi

# ── 10. Set permissions ───────────────────────────────────────
echo "🔒 Setting permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 775 storage bootstrap/cache

# ── 11. Run migrations ────────────────────────────────────────
echo "🗃️  Running migrations..."
php artisan migrate --force
php artisan storage:link

# ── 12. Cache config ──────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 13. Configure Nginx ───────────────────────────────────────
echo "🌐 Configuring Nginx..."
cp "$APP_DIR/deployment/nginx.conf" /etc/nginx/sites-available/sinemani
ln -sf /etc/nginx/sites-available/sinemani /etc/nginx/sites-enabled/sinemani
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── 14. Setup Queue Worker with Supervisor ─────────────────────
echo "⚡ Setting up queue worker..."
cat > /etc/supervisor/conf.d/sinemani-worker.conf <<'EOF'
[program:sinemani-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dramabox/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dramabox/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update
supervisorctl start sinemani-worker:*

# ── 15. Setup deploy script permissions ────────────────────────
chmod +x "$APP_DIR/deployment/deploy.sh"

# ── Done ───────────────────────────────────────────────────────
echo ""
echo "══════════════════════════════════════════════════════════"
echo "  ✅ Server Setup Complete!"
echo "══════════════════════════════════════════════════════════"
echo ""
echo "  🌐 URL:       http://146.190.113.112"
echo "  📂 App Dir:   $APP_DIR"
echo "  🗄️  Database:  $DB_NAME"
echo "  👤 DB User:   $DB_USER"
echo "  🔑 DB Pass:   $DB_PASS"
echo ""
echo "  ⚠️  SAVE THE DATABASE PASSWORD ABOVE!"
echo ""
echo "  Next steps:"
echo "  1. Add GitHub Secrets (see README)"
echo "  2. Push to 'main' branch to auto-deploy"
echo ""
