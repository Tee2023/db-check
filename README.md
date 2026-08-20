# DB Check

Internal Laravel tool สำหรับตรวจสอบและซ่อมแซม Database Schema ให้ตรงกับ Schema ที่กำหนดไว้ในโค้ด ใช้แก้ปัญหา "database ไม่ตรงกับที่ dev คนอื่นแก้ไว้" หลัง pull โค้ดใหม่

## Prerequisites

- PHP `^8.3`
- Composer
- MySQL
- Laravel Framework `^13.17` (ติดตั้งผ่าน Composer อัตโนมัติ)

## Database Schema Checker (`db:fix-missing`)

เครื่องมือช่วย Backend Developer ตรวจสอบว่า Database จริงตรงกับ Schema ที่กำหนดไว้ในโค้ด (`database/Schema/*.php`) หรือไม่ ถ้าพบ Column ที่หายไป, Type ไม่ตรง, หรือ Table หายไป จะช่วยสร้าง Migration ให้อัตโนมัติ แก้ปัญหา "database ไม่ตรงกับที่ dev คนอื่นแก้ไว้" หลัง pull โค้ดใหม่


### 0. ติดตั้ง Dependencies (ครั้งแรกหลัง clone)

```bash
composer install
cp .env.example .env
php artisan key:generate
```

จากนั้นตั้งค่า `.env` ให้ตรงกับ Database ของเครื่องตัวเอง (`DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)

> หมายเหตุ: `.env.example` ตั้งค่า default เป็น `DB_CONNECTION=sqlite` แต่โปรเจกต์นี้ใช้ MySQL ต้องแก้เป็น `DB_CONNECTION=mysql` และเปิด comment `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` ให้ตรงกับเครื่องตัวเอง

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

### 2. สร้าง Database

`php artisan migrate` จะสร้างได้แค่ Table เท่านั้น ไม่ได้สร้าง Database ให้เอง ต้องสร้าง Database ตามชื่อที่ตั้งไว้ใน `DB_DATABASE` (ใน `.env`) เองก่อน เช่น:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS dbCheck"
```

> เปลี่ยน `dbCheck` เป็นชื่อที่ตรงกับ `DB_DATABASE` ใน `.env` ของตัวเอง

### 3. Run Migration พื้นฐานของโปรเจกต์ (ครั้งแรกหลัง clone)

ก่อนตรวจสอบ Schema ต้องมั่นใจก่อนว่า Database มีตารางพื้นฐานของ Laravel (`users`, `cache`, `jobs` ฯลฯ) อยู่แล้ว ถ้ายังไม่เคย Migrate บนเครื่องนี้มาก่อน ให้รัน:

```bash
php artisan migrate
```

> หมายเหตุ: นี่คือการ Migrate ตารางพื้นฐานของโปรเจกต์ ไม่ใช่ Migration ที่ `db:fix-missing` สร้างขึ้น (อันนั้นจะรันในขั้นตอนที่ 8)

### 4. ตรวจสอบอย่างเดียว (Dry Run)

```bash
php artisan db:fix-missing --dry-run
```

### 5. ตรวจ + สร้าง Migration

```bash
php artisan db:fix-missing
```

### 6. เปิดดู Migration ที่สร้าง

```
database/migrations/xxxx_xx_xx_xxxxxx_fix_missing_database_schema.php
```

### 7. ตรวจสอบ SQL/Schema ให้เรียบร้อย

เปิดไฟล์ Migration ที่สร้างขึ้น ตรวจสอบ Column/Type ให้ถูกต้องก่อน Run จริง

### 8. Run Migration


```bash
php artisan migrate
```

> Tip: ใช้ flag `--run` เพื่อ Run Migration ทันทีหลังสร้าง โดยไม่ต้องรันคำสั่งแยก:
> ```bash
> php artisan db:fix-missing --run
> ```

### สรุป Options ของคำสั่ง

| Option | คำอธิบาย |
|---|---|
| (ไม่มี option) | ตรวจสอบ Schema และสร้าง Migration ถ้าพบความแตกต่าง |
| `--dry-run` | ตรวจสอบอย่างเดียว ไม่สร้าง Migration และไม่แก้ Database |
| `--run` | สร้าง Migration แล้ว Run ทันที (มี prompt ยืนยันก่อน) |

## การเพิ่ม/แก้ Schema ที่คาดหวัง

Schema ที่ใช้เทียบกับ Database จริงถูกกำหนดไว้ที่ `database/Schema/*.php` (เช่น `UserSchema.php`, `PostSchema.php`) แต่ละไฟล์มี method `definition()` คืนค่าเป็น array ของ column โดยแต่ละ column กำหนด key ได้ดังนี้:

- `type` — ชนิดข้อมูล เช่น `bigInteger`, `string`, `text`, `boolean`, `date`, `timestamp`, `enum`
- `length` — ความยาว (สำหรับ `string`)
- `nullable` — `true`/`false`
- `default` — ค่า default
- `unsigned`, `autoIncrement`, `primary` — ใช้กับ column `id`
- `values` — รายการค่าที่เป็นไปได้ (สำหรับ `enum`)

ตัวอย่างจาก `PostSchema.php`:

```php
'status' => [
    'type' => 'enum',
    'values' => ['draft', 'published', 'archived'],
    'nullable' => false,
    'default' => 'draft',
],
```

เมื่อเพิ่ม Table ใหม่ ให้สร้างไฟล์ Schema ใหม่ในโฟลเดอร์นี้ แล้วเพิ่มลงใน `getExpectedSchema()` ของ `app/Console/Commands/FixMissingColumns.php`

## Known Issues

- Column ที่ตรวจพบว่าเป็น "EXTRA COLUMN" (มีใน Database แต่ไม่มีใน Schema ที่กำหนด) จะถูกแจ้งเตือนใน Console เท่านั้น เครื่องมือจะ **ไม่ลบ** Column นั้นให้อัตโนมัติ (เพื่อความปลอดภัย ป้องกันข้อมูลหาย) หากต้องการลบ ให้แก้ไข Migration ที่สร้างขึ้นเพิ่มเติมเอง

