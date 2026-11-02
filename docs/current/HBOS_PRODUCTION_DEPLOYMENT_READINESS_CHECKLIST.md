# SkyraSoft HBOS — Production Deployment Readiness Checklist

**Policy:** Production Launch Remains **NO-GO** Until All Preflight Items Are Certified.  

---

## 1. Infrastructure Preflight Checklist

- [ ] **Dedicated Server:** Production host running Linux (Ubuntu 24.04 LTS / Debian 12) or Windows Server.
- [ ] **PHP Runtime:** PHP 8.2+ with `pdo_mysql`, `bcmath`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `zip`.
- [ ] **MariaDB Instance:** MariaDB 10.11 LTS or 10.4+ with InnoDB engine, `utf8mb4_unicode_ci`, binary logging enabled (`log_bin=ON`, `binlog_format=ROW`).
- [ ] **SSL / TLS:** Valid certificate configured for HTTPS and API endpoints.
- [ ] **Web Server:** Nginx or Apache with reverse proxy rules, secure headers, and CORS configured strictly for counter origins.

---

## 2. Application & Security Configuration

- [ ] `APP_ENV=production` set in environment.
- [ ] `APP_DEBUG=false` strictly enforced.
- [ ] Unique `APP_KEY` generated via `php artisan key:generate`.
- [ ] Database credentials isolated; root user prohibited from application access.
- [ ] Configuration and routes cached via `php artisan config:cache` and `php artisan route:cache`.
- [ ] Storage permissions verified: `storage/` and `bootstrap/cache/` writable by web service user.

---

## 3. Operational & Financial Verification

- [ ] Clean production database migrated via `php artisan migrate --force`.
- [ ] Initial super-admin and tenant setup wizard executed.
- [ ] Thermal receipt printer, barcode scanner, and cash drawer verified on counter terminal.
- [ ] Merchant leadership operational sign-off completed.
- [ ] Formal launch approval granted by program directors.
