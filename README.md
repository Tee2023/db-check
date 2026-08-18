<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Database Schema Checker (`db:fix-missing`)

เครื่องมือช่วย Backend Developer ตรวจสอบว่า Database จริงตรงกับ Schema ที่กำหนดไว้ในโค้ด (`database/Schema/*.php`) หรือไม่ ถ้าพบ Column ที่หายไป, Type ไม่ตรง, หรือ Table หายไป จะช่วยสร้าง Migration ให้อัตโนมัติ แก้ปัญหา "database ไม่ตรงกับที่ dev คนอื่นแก้ไว้" หลัง pull โค้ดใหม่

### 0. ติดตั้ง Dependencies (ครั้งแรกหลัง clone)

```bash
composer install
cp .env.example .env
php artisan key:generate
```

จากนั้นตั้งค่า `.env` ให้ตรงกับ Database ของเครื่องตัวเอง (`DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)

### 1. เริ่มต้น MySQL

ก่อนใช้งาน Laravel และตรวจสอบ Database ให้ตรวจสอบว่า MySQL ทำงานอยู่ก่อน

```bash
brew services start mysql
```

หาก MySQL ทำงานสำเร็จ จะขึ้นประมาณ:

```
Successfully started `mysql`
```

ตรวจสอบสถานะ MySQL:

```bash
brew services list
```

ควรเห็น:

```
mysql started
```

### 2. ตรวจสอบอย่างเดียว (Dry Run)

```bash
php artisan db:fix-missing --dry-run
```

### 3. ตรวจ + สร้าง Migration

```bash
php artisan db:fix-missing
```

### 4. เปิดดู Migration ที่สร้าง

```
database/migrations/xxxx_xx_xx_xxxxxx_fix_missing_database_schema.php
```

### 5. ตรวจสอบ SQL/Schema ให้เรียบร้อย

เปิดไฟล์ Migration ที่สร้างขึ้น ตรวจสอบ Column/Type ให้ถูกต้องก่อน Run จริง

### 6. Run Migration

```bash
php artisan migrate
```

> Tip: ใช้ flag `--run` เพื่อ Run Migration ทันทีหลังสร้าง โดยไม่ต้องรันคำสั่งแยก:
> ```bash
> php artisan db:fix-missing --run
> ```

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
