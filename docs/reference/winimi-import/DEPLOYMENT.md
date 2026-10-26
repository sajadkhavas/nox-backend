# راهنمای راه‌اندازی روی سرور شخصی (Ubuntu 22.04 / 24.04)

> این فایل تمام دستورهای **خط به خط** برای انتقال این بکند از GitHub روی سرور Ubuntu را شامل می‌شود.
> همه‌جا `toolmaster.com` و `api.toolmaster.com` را با دامنه‌ی خودتان عوض کنید.

---

## مرحله ۱ — نصب پیش‌نیازها

```bash
# اتصال SSH به سرور
ssh root@YOUR_SERVER_IP

# به‌روزرسانی سیستم
apt update && apt upgrade -y

# نصب PHP 8.3 و افزونه‌ها
apt install -y software-properties-common ca-certificates lsb-release
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
  php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml \
  php8.3-bcmath php8.3-intl php8.3-redis php8.3-tokenizer php8.3-fileinfo

# نصب Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# نصب Nginx + MySQL + Redis + Git + Certbot
apt install -y nginx mysql-server redis-server git certbot python3-certbot-nginx unzip

# راه‌اندازی سرویس‌ها
systemctl enable --now nginx mysql redis-server php8.3-fpm
```

---

## مرحله ۲ — ساخت دیتابیس

```bash
mysql_secure_installation
mysql -u root -p
```
داخل MySQL:
```sql
CREATE DATABASE toolmaster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'toolmaster'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON toolmaster.* TO 'toolmaster'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## مرحله ۳ — کلون پروژه از GitHub

```bash
cd /var/www
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git toolmaster
cd toolmaster/backend     # چون بکند داخل پوشه‌ی backend است

# نصب وابستگی‌ها
composer install --no-dev --optimize-autoloader

# ساخت .env
cp .env.example .env
nano .env
```

داخل `.env` این مقادیر را تنظیم کنید:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.toolmaster.com
FRONTEND_URL=https://toolmaster.com

DB_DATABASE=toolmaster
DB_USERNAME=toolmaster
DB_PASSWORD=STRONG_PASSWORD_HERE

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1

MAIL_HOST=smtp.YOUR_PROVIDER.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=info@toolmaster.com
MAIL_ADMIN_ADDRESS=admin@toolmaster.com
```

ذخیره: `Ctrl+O`, `Enter`, `Ctrl+X`

---

## مرحله ۴ — راه‌اندازی Laravel

```bash
# Key + Migrate + Seed
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

# Cache (سرعت بالاتر در production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize

# اجازه‌ها (مهم!)
chown -R www-data:www-data /var/www/toolmaster
chmod -R 755 /var/www/toolmaster
chmod -R 775 /var/www/toolmaster/backend/storage /var/www/toolmaster/backend/bootstrap/cache
```

---

## مرحله ۵ — تنظیمات Nginx

```bash
nano /etc/nginx/sites-available/api.toolmaster.com
```

محتوای فایل:
```nginx
server {
    listen 80;
    server_name api.toolmaster.com;
    root /var/www/toolmaster/backend/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Service-Worker-Allowed "/";

    charset utf-8;
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* { deny all; }

    # Cache-Control برای API (مهم برای PWA)
    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
    }

    location /storage/ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
    }

    access_log /var/log/nginx/api.toolmaster.access.log;
    error_log  /var/log/nginx/api.toolmaster.error.log;
}
```

فعال‌سازی:
```bash
ln -s /etc/nginx/sites-available/api.toolmaster.com /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## مرحله ۶ — SSL (HTTPS با Let's Encrypt)

> برای PWA الزامی است.

```bash
certbot --nginx -d api.toolmaster.com
# گزینه‌ی Redirect to HTTPS را انتخاب کنید (option 2)

# تمدید خودکار
systemctl enable --now certbot.timer
```

---

## مرحله ۷ — Queue Worker (برای ایمیل‌ها)

```bash
nano /etc/systemd/system/toolmaster-worker.service
```

```ini
[Unit]
Description=ToolMaster Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/toolmaster/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/log/toolmaster-worker.log
StandardError=append:/var/log/toolmaster-worker.log

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload
systemctl enable --now toolmaster-worker
systemctl status toolmaster-worker
```

---

## مرحله ۸ — Cron برای Scheduler

```bash
crontab -e -u www-data
```

این خط را اضافه کنید:
```
* * * * * cd /var/www/toolmaster/backend && php artisan schedule:run >> /dev/null 2>&1
```

این کرون به‌طور خودکار: تولید روزانه‌ی sitemap.xml، پاکسازی job batchها و... را اجرا می‌کند.

---

## مرحله ۹ — تست

```bash
# تست API
curl https://api.toolmaster.com/api/v1/categories | jq

# تست health
curl https://api.toolmaster.com/health

# مرور پنل ادمین
# مرورگر → https://api.toolmaster.com/admin
# Email: admin@toolmaster.com
# Pass:  Admin@2025!Change  ← حتماً بعد از ورود تغییر دهید
```

---

## مرحله ۱۰ — استقرار فرانت React (PWA)

```bash
cd /var/www/toolmaster        # ریشه‌ی پروژه (نه backend)

# نصب Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# نصب bun (اختیاری ولی سریع‌تر)
curl -fsSL https://bun.sh/install | bash

# نصب پکیج‌ها + Build
bun install
echo "VITE_API_BASE_URL=https://api.toolmaster.com/api/v1" > .env.production
bun run build
```

Nginx برای فرانت `toolmaster.com`:
```bash
nano /etc/nginx/sites-available/toolmaster.com
```

```nginx
server {
    listen 80;
    server_name toolmaster.com www.toolmaster.com;
    root /var/www/toolmaster/dist;
    index index.html;

    # PWA: cache headers
    location = /sw.js              { add_header Cache-Control "no-cache"; }
    location = /manifest.webmanifest { add_header Cache-Control "no-cache"; }

    location ~* \.(js|css|png|jpg|jpeg|gif|svg|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/toolmaster.com /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d toolmaster.com -d www.toolmaster.com
```

---

## ✅ به‌روزرسانی‌های بعدی

برای deploy نسخه‌ی جدید از GitHub:
```bash
cd /var/www/toolmaster
git pull origin main

# Backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
systemctl restart php8.3-fpm

# Frontend
cd ..
bun install
bun run build
```

---

## 🔒 چک‌لیست امنیتی نهایی

- [ ] رمز `admin@toolmaster.com` تغییر داده شد
- [ ] `APP_DEBUG=false` در `.env`
- [ ] HTTPS فعال است (هم api و هم frontend)
- [ ] فایروال: `ufw allow OpenSSH && ufw allow 'Nginx Full' && ufw enable`
- [ ] بک‌آپ روزانه‌ی دیتابیس:
  ```bash
  echo "0 3 * * * mysqldump toolmaster -u toolmaster -p'PASS' | gzip > /backup/db-\$(date +\%F).sql.gz" | crontab -
  ```
- [ ] `chmod 600 .env`

---

## 🛟 رفع اشکال‌های رایج

| مشکل | راه‌حل |
|------|--------|
| 500 Error | `tail -f backend/storage/logs/laravel.log` |
| Permission denied | `chown -R www-data:www-data backend/storage backend/bootstrap/cache` |
| CORS error | `FRONTEND_URL` در `.env` بکند درست تنظیم شده؟ |
| ایمیل نمی‌رود | `systemctl status toolmaster-worker` + لاگ‌های `/var/log/toolmaster-worker.log` |
| Filament login نمی‌شود | کش پاک: `php artisan optimize:clear` |
| 404 روی routeها | `php artisan route:cache` |
