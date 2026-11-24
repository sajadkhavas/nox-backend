# بک‌اند NOX، بستهٔ مستقل از Lovable

سورس Laravel موجود NOX با اصلاحات سرور LBB ادغام شده است. این ZIP را به Lovable برای ساخت بک‌اند جدید نده؛ API فعلی حفظ می‌شود. اطلاعات محیط و داده‌ها در بسته نیست.

قبل از اعمال روی لپ‌تاپ از سورس و دیتابیس خودت backup بگیر. فایل‌های .env، vendor و storage محلی را نگه دار؛ روی LBB اجرا نکن. ابتدا APP_ENV، نام دیتابیس و URL را برای NOX کنترل کن. دو migration جدید FAQ و تصویر راهنمای سایز افزایشی‌اند. برای دیتابیس موجود key:generate را دوباره اجرا نکن؛ APP_KEY فعلی را حفظ کن.

پس از ادغام فایل‌ها در محیط NOX:
composer install --prefer-dist --no-interaction
php artisan config:clear
php artisan migrate --pretend
php artisan migrate
php artisan test

در صورت نیاز فقط روی local و دیتابیس nox می‌توان دسته‌ها را با php artisan db:seed --class=NoxCategorySeeder ایجاد کرد. نمونه‌های محصول اختیاری و draft/inactive با NoxSampleProductSeeder ساخته می‌شوند؛ قیمت یا موجودی ساختگی اضافه نمی‌کنند. seedها خودکار اجرا نمی‌شوند و رسانه یا محصول قبلی را بازنویسی نمی‌کنند. تغییرات جزئی دسته‌های موجود را در پنل بررسی کن.

تست runtime لاراول در محیط آماده‌سازی اجرا نشده است. فایل‌های PHP تغییرکرده از نظر syntax parse شده‌اند. سفارش، احراز هویت، آدرس، فاکتور و پیگیری باید با API واقعی NOX کنترل شوند. تنظیمات payment/SMS/CORS/SSR token/queue باید مختص NOX بمانند.
