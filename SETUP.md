# POS System — Setup Guide

## Step 1: Copy files into your Laravel project

Copy all files from this folder into your Laravel project root.
Merge/replace existing files when asked.

---

## Step 2: Create your .env file

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials:

```
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

## Step 3: Install dependencies

```bash
composer install
npm install
```

---

## Step 4: Install Laravel Breeze (Auth)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

---

## Step 5: Run migrations

```bash
php artisan migrate
```

---

## Step 6: Run demo seeder (optional — adds sample data)

```bash
php artisan db:seed --class=DemoSeeder
```

Demo login:
- Email: admin@pos.com
- Password: password

---

## Step 7: Link storage

```bash
php artisan storage:link
```

---

## Step 8: Build assets & run

```bash
npm run build
php artisan serve
```

Open: http://localhost:8000

---

## Files added by this package

| File | Purpose |
|------|---------|
| `resources/views/layouts/app.blade.php` | Main layout with sidebar |
| `resources/views/dashboard/index.blade.php` | Dashboard with stats |
| `resources/views/categories/*` | Category CRUD views |
| `resources/views/customers/*` | Customer CRUD views |
| `resources/views/suppliers/*` | Supplier CRUD views |
| `resources/views/invoices/*` | Invoice views (list, create, view) |
| `resources/views/products/*` | Product CRUD views |
| `app/Http/Controllers/*Controller.php` | All controllers |
| `app/Models/*.php` | All Eloquent models |
| `database/migrations/*.php` | Database tables |
| `database/seeders/DemoSeeder.php` | Sample data |
| `routes/web.php` | All web routes |
| `routes/auth.php` | Auth routes |
| `.env.example` | Environment template |
