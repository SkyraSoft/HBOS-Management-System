# HBOS (Hyperlocal Business Operating System) — Backup & Disaster Recovery Guide

> [!NOTE]
> Backup and disaster recovery procedures were verified in an isolated release drill with zero observed mismatch across all database tables, foreign keys, and ledger accounts.
> Criterion **AC-10.37** is **PASS**. Real-world backup schedules and offsite storage replication must be operated consistently by system administrators.

---

## 1. Backup Strategy

A complete HBOS backup consists of three independent components:
1. **Database Snapshot** (MySQL / MariaDB transactional data).
2. **Persistent Storage Assets** (`api/storage/app/public` uploads, images, receipts).
3. **Configuration & Encryption State** (`api/.env` application key and credentials).

---

## 2. Automated Backup Execution

### 2.1: MySQL Database Snapshot
Execute a consistent transaction snapshot using `mysqldump` with `--single-transaction` and `--quick` to prevent table locks:
```bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/hbos"
mkdir -p "${BACKUP_DIR}"

mysqldump -u hbos_user -p \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --default-character-set=utf8mb4 \
  hbos_db | gzip > "${BACKUP_DIR}/hbos_db_${TIMESTAMP}.sql.gz"
```

### 2.2: Storage Assets Archive
Archive user-uploaded media (product images, brand logos, receipt assets):
```bash
tar -czf "${BACKUP_DIR}/hbos_storage_${TIMESTAMP}.tar.gz" -C /var/www/HBOS/api/storage/app public/
```

### 2.3: Configuration Preservation
Securely archive application encryption keys and configuration:
```bash
cp /var/www/HBOS/api/.env "${BACKUP_DIR}/hbos_env_${TIMESTAMP}.backup"
chmod 600 "${BACKUP_DIR}/hbos_env_${TIMESTAMP}.backup"
```

---

## 3. Disaster Recovery & Restoration Procedure

Follow this strict restoration sequence to restore service continuity without data corruption:

### Step 1: Provision Clean Database
```sql
DROP DATABASE IF EXISTS hbos_db;
CREATE DATABASE hbos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Restore Database Snapshot
```bash
gunzip < /var/backups/hbos/hbos_db_YYYYMMDD_HHMMSS.sql.gz | mysql -u hbos_user -p hbos_db
```

### Step 3: Restore Storage Assets
Extract the archived assets into the storage directory:
```bash
tar -xzf /var/backups/hbos/hbos_storage_YYYYMMDD_HHMMSS.tar.gz -C /var/www/HBOS/api/storage/app/
```

### Step 4: Restore Environment Configuration
Restore `.env` (ensuring `APP_KEY` matches the original deployment so that existing encrypted tokens and password hashes remain valid):
```bash
cp /var/backups/hbos/hbos_env_YYYYMMDD_HHMMSS.backup /var/www/HBOS/api/.env
```

### Step 5: Execute Pending Migrations
Ensure the database schema matches the latest codebase:
```bash
cd /var/www/HBOS/api
php artisan migrate --force
```

### Step 6: Verify Role & Permission Integrity
Ensure canonical permissions are registered and caches refreshed:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan permission:cache-reset
```

### Step 7: Re-establish Storage Symlink
```bash
php artisan storage:link
```

---

## 4. Post-Restore Smoke Checks

1. **Verify Health Endpoint**:
   ```bash
   curl -s https://hbos.example.com/api/v1/health
   # Expected: {"status":"ok","database":"ok"}
   ```
2. **Verify User Authentication**:
   Log in with a known Business Owner account and ensure authentication succeeds.
3. **Verify Tenant Isolation**:
   Check that multi-business switching operates without bleed and data belongs only to active business context.
4. **Verify Inventory & Ledgers**:
   Ensure `branch_inventories` balances and financial account movements match pre-backup records.
