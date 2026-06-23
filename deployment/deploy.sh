#!/bin/bash
set -e

# ── Configuration ───────────────────────────────────────────────
APP_DIR="/var/www/dramabox"
BRANCH="main"

echo "🚀 Deploying sinemani..."

cd "$APP_DIR"

# Put application in maintenance mode
php artisan down --retry=60 || true

# Pull latest code
git pull origin "$BRANCH"

# Install/update dependencies (no dev packages in production)
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run database migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi
export APP_KEY="$(grep ^APP_KEY= .env | cut -d= -f2-)"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Link storage if not already done
php artisan storage:link 2>/dev/null || true

# Set correct permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart queue workers (if running)
php artisan queue:restart 2>/dev/null || true

# Reload PHP-FPM to pick up config changes
systemctl reload php8.3-fpm 2>/dev/null || true

# Bring application back up
php artisan up

echo "✅ Deployment complete!"
