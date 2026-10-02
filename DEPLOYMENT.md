# Render deployment guide

## Method 1: Via render.yaml (Blueprint)

1. ارفع هذا المشروع إلى GitHub repo جديد (مثلاً `water-filling-laravel`)
2. سجّل دخول على https://dashboard.render.com
3. اذهب إلى **Blueprints** → **New blueprint instance**
4. اختر الـ repo الذي رفعت المشروع إليه
5. Render سيقرأ `render.yaml` وينشئ:
   - **Web Service** (PHP/Laravel) على plan مجاني
   - **PostgreSQL** على plan مجاني
6. Render سيبني الصورة عبر Dockerfile وينشر تلقائياً
7. بعد النشر، تحقق من `/up` للتأكد من عمل التطبيق

## Method 2: Via Render API (نشر آلي)

استخدمنا Render API في هذه الجلسة لإنشاء الـ Web Service. النشر يتم تلقائياً عند أي push إلى الـ main branch.

## Environment Variables المطلوبة

Render سيضبطها تلقائياً من render.yaml:

| Variable | Value | Source |
|---|---|---|
| `APP_ENV` | `production` | ثابت |
| `APP_DEBUG` | `false` | ثابت |
| `DB_CONNECTION` | `pgsql` | ثابت |
| `DATABASE_URL` | (Render يضبطها تلقائياً من قاعدة البيانات) | secret |

## بعد أول نشر

1. **APP_KEY**: Render سيضبطه تلقائياً عبر `php artisan key:generate` في الـ CMD
2. **Migrations**: تُنفَّذ تلقائياً عند بدء التشغيل
3. أنشئ مستخدم admin عبر `php artisan tinker`:
   ```php
   \App\Models\User::create([
     'name' => 'Admin',
     'email' => 'admin@example.com',
     'password' => bcrypt('Admin@2026'),
     'email_verified_at' => now(),
     'role' => 'SUPER_ADMIN',
   ]);
   ```
