# HBOS (Hyperlocal Business Operating System) — Production Configuration Guide

This guide details all configuration settings, security parameters, and operational rules required to run HBOS in a production environment.

---

## 1. Environment Variables Overview (`api/.env`)

```ini
# Application Identity & Lifecycle
APP_NAME=HBOS
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://hbos.example.com
APP_TIMEZONE=Asia/Karachi

# Logging Settings
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration (MySQL / MariaDB)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hbos_production
DB_USERNAME=hbos_user
DB_PASSWORD=YOUR_STRONG_PASSWORD

# Session Driver
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# CORS Configuration
# Must be comma-separated explicit URLs without wildcards
CORS_ALLOWED_ORIGINS=https://hbos.example.com,https://app.hbos.example.com
SANCTUM_STATEFUL_DOMAINS=hbos.example.com,app.hbos.example.com

# File Storage
FILESYSTEM_DISK=public

# Queue & Cache
CACHE_STORE=database
QUEUE_CONNECTION=database

# Email Delivery (SMTP Example)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@hbos.example.com
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@hbos.example.com"
MAIL_FROM_NAME="HBOS Operations"
```

---

## 2. HTTPS & Reverse Proxy Assumptions

1. **TLS / HTTPS Termination**:
   - Production deployments **must** terminate HTTPS at the reverse proxy (Nginx, Caddy, Cloudflare, AWS ALB).
   - Ensure the proxy passes standard forwarding headers:
     ```nginx
     proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
     proxy_set_header X-Forwarded-Proto $scheme;
     proxy_set_header X-Forwarded-Host $host;
     proxy_set_header Host $host;
     ```
2. **Trusted Proxies**:
   - Configure Laravel's trusted proxies in `bootstrap/app.php` or middleware if behind a reverse proxy (e.g. `$middleware->trustProxies(at: '*');`).

---

## 3. CORS & Authentication Security

- **Wildcard Prevention**: Never configure `CORS_ALLOWED_ORIGINS=*` when credentials are supported (`supports_credentials = true`). Browsers reject credentialed requests with wildcard origins.
- **Header Parsing**: `api/config/cors.php` dynamically parses comma-separated entries from `CORS_ALLOWED_ORIGINS` and strips whitespace.
- **Tenant Context Header**: The frontend sends `X-Business-ID` on every authenticated request. It is parsed by `ResolveActiveBusiness` middleware to scope team permissions and Eloquent queries.

---

## 4. Response Security Headers

HBOS attaches baseline security headers at the application level via `App\Http\Middleware\SecurityHeaders`:
- `X-Content-Type-Options: nosniff`: Prevents MIME-type sniffing.
- `X-Frame-Options: SAMEORIGIN`: Protects against clickjacking.
- `Referrer-Policy: strict-origin-when-cross-origin`: Restricts referrer disclosure across origins.

**Reverse Proxy Supplement (Optional but Recommended)**:
The reverse proxy should additionally supply:
- `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (HSTS)
- Content Security Policy (CSP) tailored to the Vue single-page application.

---

## 5. Frontend Build Configuration (`Frontend/`)

- Set `VITE_API_BASE_URL` during build time if the API resides on a dedicated subdomain:
  ```bash
  VITE_API_BASE_URL=https://api.hbos.example.com/api/v1 npm run build
  ```
- If the frontend and backend are hosted on the same origin via path routing (e.g., `/` for frontend and `/api/v1` for backend), omit `VITE_API_BASE_URL` to automatically default to relative path `/api/v1`.

---

## 6. Operational Health Check

- Endpoint: `GET /api/v1/health`
- Purpose: Verifies application boot and active database connection pool.
- Guarantees: Does **not** disclose internal database name, credentials, host, or SQL error stacks.
- Status Codes:
  - `200 OK`: Database connectivity verified (`status: "ok"`, `database: "ok"`).
  - `503 Service Unavailable`: Connection failed or pool exhausted (`status: "degraded"`, `database: "unavailable"`).
