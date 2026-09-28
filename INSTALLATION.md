# HBOS (Hyperlocal Business Operating System) — Installation Guide

## 1. System Requirements

- **Operating System**: Linux (Ubuntu 22.04+ LTS recommended), Windows (with WSL2 or Native PHP/Node), macOS
- **PHP**: PHP >= 8.2 (tested up to PHP 8.3)
  - Required PHP Extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `fileinfo`, `gd`
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Node.js & npm**: Node.js >= 22.18.0 or >= 24.12.0, npm >= 10.8.0
- **Web Server**: Nginx, Apache HTTP Server, or Caddy
- **Composer**: Composer 2.7+

---

## 2. Directory Structure & Document Roots

```
HBOS/
├── api/                # Laravel 11 backend API (Document root: api/public)
├── Frontend/           # Vue 3 + Vite SPA (Build output: Frontend/dist)
├── docs/               # Architecture & phase verification documentation
├── database_backups/   # Database snapshots (excluded from release packages)
```

---

## 3. Backend Setup (`api/`)

### Step 3.1: Install PHP Dependencies
Navigate to the `api` directory and install composer packages without dev dependencies for production:
```bash
cd api
composer install --no-dev --optimize-autoloader
```
*(For development environments, run `composer install` without flags).*

### Step 3.2: Environment Configuration
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Configure your database connection, URL, and timezone in `.env`:
```ini
APP_NAME=HBOS
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://hbos.example.com
APP_TIMEZONE=Asia/Karachi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hbos_db
DB_USERNAME=hbos_user
DB_PASSWORD=your_secure_password

CORS_ALLOWED_ORIGINS=https://hbos.example.com,https://app.hbos.example.com
SANCTUM_STATEFUL_DOMAINS=hbos.example.com,app.hbos.example.com
```

### Step 3.3: Generate Application Key
```bash
php artisan key:generate
```

### Step 3.4: Database Provisioning & Migrations
Create your database if it does not exist:
```sql
CREATE DATABASE hbos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Execute the 54 canonical migrations in batch order:
```bash
php artisan migrate --force
```

### Step 3.5: Seed Roles & Core Permissions
Run the canonical permission and role seeder to initialize the three canonical roles (`Business Owner`, `Branch Manager`, `Salesperson`) and permission registry:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

### Step 3.6: Symlink Storage Directory
Create the public storage link for product image and receipt asset access:
```bash
php artisan storage:link
```

---

## 4. Frontend Setup (`Frontend/`)

### Step 4.1: Install Node Dependencies
Navigate to `Frontend` directory:
```bash
cd ../Frontend
npm ci
```

### Step 4.2: Configure Environment
Create `.env` or `.env.production` if using a standalone API domain:
```ini
VITE_API_BASE_URL=https://hbos.example.com/api/v1
```
*(If omitted, the frontend defaults to `/api/v1` which is recommended when reverse-proxied behind the same domain).*

### Step 4.3: Build Production Assets
```bash
npm run build
```
Production assets are generated in `Frontend/dist`.

---

## 5. Web Server Configuration

### Nginx Example (Reverse Proxy + Static Assets)
```nginx
server {
    listen 443 ssl http2;
    server_name hbos.example.com;

    # Frontend Single Page App
    root /var/www/HBOS/Frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API Routing
    location /api/ {
        alias /var/www/HBOS/api/public/;
        try_files $uri $uri/ @backend;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME /var/www/HBOS/api/public/index.php;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        }
    }

    location @backend {
        rewrite ^/api/(.*)$ /index.php/$1 last;
    }

    # Storage Assets Link
    location /storage/ {
        alias /var/www/HBOS/api/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

---

## 6. Initial Health Verification

Run the release health check endpoint to confirm application boot and database connectivity:
```bash
curl -I https://hbos.example.com/api/v1/health
```
Expected response:
```json
HTTP/1.1 200 OK
Content-Type: application/json
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin

{
  "status": "ok",
  "database": "ok"
}
```
If this returns `200 OK`, your HBOS instance is operational.
