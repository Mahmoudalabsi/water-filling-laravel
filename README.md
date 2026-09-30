# Water Filling App (Laravel)

تطبيق إدارة اشتراكات المياه (غواطس) مبني بـ **Laravel 11** و **Blade Views** + **Alpine.js** + **tesseract.js**.

## الميزات

- ✅ نموذج عائلة واحدة مبسّط (بدون هيكلية أصحاب الغواطس)
- ✅ تسجيل دخول + إنشاء حساب (مع تأكيد بريد اختياري)
- ✅ لوحة تحكم بالعائلات + الجلسات الأسبوعية
- ✅ **قراءة عداد الكهرباء بالصور (OCR مجاني عبر tesseract.js)**
- ✅ حساب **سعر الدقيقة** تلقائياً (kWh × تعرفة ÷ مدة بالدقيقة)
- ✅ إعدادات: تعرفة الكهرباء (₪/kWh) + سحب الغاطس (kW)

## التشغيل المحلي

```bash
composer install
cp .env.example .env
php artisan key:generate
# عدّل DATABASE_URL في .env
php artisan migrate
php artisan serve
```

## النشر على Render

الملفات الجاهزة للنشر:
- `Dockerfile` — صورة PHP 8.3 + PostgreSQL
- `render.yaml` — تعريف Web Service + PostgreSQL

انظر دليل النشر الكامل في `DEPLOYMENT.md`.
