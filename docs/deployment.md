# Deployment

## Production Checklist

Follow this checklist when deploying ALTechnics ERP to a production server.

### 1. Server Requirements

- PHP 8.3+ with required extensions (mbstring, xml, curl, mysql, gd, zip, bcmath)
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ (for asset compilation)
- Web server: Nginx (recommended) or Apache
- SSL certificate for HTTPS

### 2. Environment Configuration

```bash
cp .env.example .env
```

Set production values:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.altechnics.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=altechnics_erp
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password

SESSION_DRIVER=database
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

### 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate --force
```

### 6. Seed Initial Data

For first deployment only:

```bash
php artisan db:seed --force
```

### 7. Storage Link

```bash
php artisan storage:link
```

### 8. Cache Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Run these after every deployment. Clear with `php artisan optimize:clear` if needed.

### 9. Queue Worker

If using queued jobs (email, exports):

```bash
php artisan queue:work --daemon --sleep=3 --tries=3
```

Use Supervisor to keep the queue worker running:

```ini
[program:altechnics-queue]
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
```

### 10. File Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 11. Nginx Configuration

```nginx
server {
    listen 80;
    server_name erp.altechnics.com;
    root /var/www/altechnics-erp/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 12. Security Hardening

- Set `APP_DEBUG=false` (never enable debug in production)
- Use HTTPS with a valid SSL certificate
- Change all default passwords immediately after seeding
- Restrict database access to the application server only
- Enable firewall rules (UFW or iptables)
- Configure regular backups using `spatie/laravel-backup`

## Backup Configuration

The project includes `spatie/laravel-backup`. Schedule daily backups:

```php
// app/Console/Kernel.php
$schedule->command('backup:run')->dailyAt('02:00');
$schedule->command('backup:clean')->dailyAt('03:00');
```

## Deployment Script Example

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```
