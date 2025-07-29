#!/bin/bash

# SMTXchange Deployment Script
# This script helps deploy the SMTXchange investment platform

echo "🚀 SMTXchange Deployment Script"
echo "================================"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please copy .env.example to .env and configure it first."
    exit 1
fi

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# Install NPM dependencies
echo "📦 Installing NPM dependencies..."
npm install

# Generate application key if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Run database migrations
echo "🗄️  Running database migrations..."
read -p "Do you want to run fresh migrations with seeders? This will delete all existing data. (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate:fresh --seed --force
else
    php artisan migrate --force
fi

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Clear and cache configuration
echo "⚡ Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for production
echo "🚀 Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔒 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
if command -v chown &> /dev/null; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "⚠️  Could not set ownership. Please run: sudo chown -R www-data:www-data storage bootstrap/cache"
fi

# Create symbolic link for storage
echo "🔗 Creating storage symbolic link..."
php artisan storage:link

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Next Steps:"
echo "1. Configure your web server to point to the 'public' directory"
echo "2. Set up SSL certificate for HTTPS"
echo "3. Add cron job for scheduled tasks:"
echo "   * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo "4. Change the default admin password (admin@smtxchange.com / admin123)"
echo ""
echo "🌐 Admin Panel: /admin"
echo "📧 Default Admin: admin@smtxchange.com"
echo "🔑 Default Password: admin123"
echo ""
echo "⚠️  IMPORTANT: Change the admin password immediately after first login!"

