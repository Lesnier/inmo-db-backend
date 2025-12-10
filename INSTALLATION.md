# InmoDB System Installation Guide

This guide details exactly how to set up the complete environment for the InmoDB Real Estate CRM, including all necessary services.

## 1. Core Requirements

- **PHP 8.2+** with extensions: `pdo_mysql`, `redis`, `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `tokenizer`, `xml`.
- **MySQL 8.0+**
- **Composer**
- **Node.js & NPM**
- **Redis Server** (Crucial for Caching, Queues, and Reverb/WebSockets)

## 2. Redis Installation (Windows)

We use the native Redis port for Windows (Memurai or Microsoft archive) or WSL2.
**Recommended for Dev**: [Redis for Windows (GitHub)](https://github.com/microsoftarchive/redis/releases) (Download `.msi` and install).

1.  **Verify Service**: Open PowerShell as Admin and run `Get-Service redis`. Status should be `Running`.
2.  **Verify Connection**: Run `redis-cli ping`. Response should be `PONG`.

## 3. Application Setup

1.  **Clone & Install Dependencies**
    ```powershell
    git clone <repo_url>
    cd inmo-db-backend
    composer install
    npm install
    ```

2.  **Environment Configuration**
    Copy `.env.example` to `.env` and configure:
    ```ini
    APP_URL=http://localhost:8000

    # Database
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=inmo_db
    DB_USERNAME=root
    DB_PASSWORD=

    # Redis (Cache, Queues, Reverb)
    REDIS_HOST=127.0.0.1
    REDIS_PASSWORD=null
    REDIS_PORT=6379
    REDIS_CLIENT=predis

    # High Performance Drivers
    CACHE_DRIVER=redis
    QUEUE_CONNECTION=redis
    SESSION_DRIVER=redis

    # Reverb (WebSockets)
    REVERB_APP_ID=my-app-id
    REVERB_APP_KEY=my-app-key
    REVERB_APP_SECRET=my-app-secret
    REVERB_HOST="localhost"
    REVERB_PORT=8080
    REVERB_SCHEME=http

    VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
    VITE_REVERB_HOST="${REVERB_HOST}"
    VITE_REVERB_PORT="${REVERB_PORT}"
    VITE_REVERB_SCHEME="${REVERB_SCHEME}"
    ```

3.  **Generate Keys & Database**
    ```powershell
    php artisan key:generate
    php artisan migrate:fresh --seed
    # This runs DatabaseSeeder which populates Roles, Admin, Real Estate Data, and CRM Data.
    ```

## 4. Running the Services

To experience the full features (Real-time Chat, CRM Automation, Async Jobs), you must run **3 separate terminals**:

### Terminal 1: Web Server
Serves the API endpoints.
```powershell
php artisan serve
```

### Terminal 2: Queue Worker
Processes background jobs (Emails, CRM Workflow listeners, Cache warm-up).
```powershell
php artisan queue:work
```

### Terminal 3: Reverb Server (WebSockets)
Handles real-time functionality for Chat and Notifications.
```powershell
php artisan reverb:start
```

## 5. Verification Checklist

1.  **Login**: Access `http://localhost:8000/admin`.
    -   Creds: `admin@admin.com` / `password`
2.  **Chat**: Open the Chat interface. Send a message. You should see it appear instantly (via Reverb) without refreshing.
3.  **CRM**: Create a new Property Inquiry.
    -   Check the `Queue Worker` terminal. You should see `Processing... PropertyContactCreated`.
    -   Go to CRM > Deals. A new deal should have appeared automatically.
4.  **Cache**: Open a Deal detail. Refresh. The second load should be faster.
    -   (Optional) Check Redis keys using `redis-cli keys *`. You should see keys like `inmo_database_crm_deal_1`.

## 6. Troubleshooting

-   **"Target class [predis] does not exist"**: Run `composer require predis/predis`.
-   **"Connection Refused" (Redis)**: Ensure Redis service is running (`Get-Service redis`).
-   **"WebSocket connection failed"**: Ensure `php artisan reverb:start` is running and port 8080 is open.

## 7. Deployment: VPS with Docker (Recommended)

Since you have a VPS with **8GB RAM** (Hostinger), this is the **BEST** option. It supports Redis, Long-running Workers, and Reverb (WebSockets) natively via Docker.

### Prerequisites (On VPS)
1.  **SSH into VPS**: `ssh root@191.101.0.20`
2.  **Install Docker & Docker Compose**:
    ```bash
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    ```

### Step-by-Step Deploy
1.  **Upload Code**:
    *   Option A (Git): `git clone https://github.com/your-repo/inmo-db-backend.git /var/www/inmo-db`
    *   Option B (SFTP): Upload your local folder to `/var/www/inmo-db`
2.  **Setup Environment**:
    ```bash
    cd /var/www/inmo-db
    cp .env.example .env
    nano .env
    ```
    *   Set `DB_HOST=db` (matches docker service name)
    *   Set `REDIS_HOST=redis`
    *   Set `REVERB_HOST="0.0.0.0"`
3.  **Start Services**:
    ```bash
    docker compose up -d --build
    ```
4.  **Finalize**:
    ```bash
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan migrate --seed
    docker compose exec app php artisan storage:link
    ```

### DNS Configuration
To point `api.inmodb.net` (or backend subdomain) to your VPS:
1.  Go to your Domain Registrar (Hostinger DNS Zone).
2.  Create an **A Record**:
    *   **Host**: `api` (creates api.inmodb.net)
    *   **Points to**: `191.101.0.20` (Your VPS IP)
    *   **TTL**: 300
3.  In your frontend app, set API URL to `http://api.inmodb.net`.

## 8. Deployment: Shared WebHosting (Test Environment)

**Strategy**: Use this environment for Staging/Demos.
*   **Limitation**: Real-time Chat (Push) won't work perfectly (fallback to polling or silent failure).
*   **Queues**: Will run via Cron Job (Schedule), not Daemon.

### Configuration Steps
1.  **Environment (.env)**:
    ```ini
    BROADCAST_CONNECTION=log
    QUEUE_CONNECTION=database
    CACHE_DRIVER=file
    # Or use Redis if provided by Hostinger, but ensure it's protected.
    ```
    *   Setting `BROADCAST_CONNECTION=log` prevents errors when Reverb is unreachable. Use `pusher` if you buy a plan.

2.  **Setup Cron Job (Crucial)**:
    In cPanel > Cron Jobs, add this **Once Per Minute**:
    ```bash
    cd /home/u403607455/domains/inmodb.net/public_html && php artisan schedule:run >> /dev/null 2>&1
    ```
    *   **What this does**: It runs the Laravel Scheduler.
    *   **How Queues work**: I have configured the Scheduler to run `queue:work --stop-when-empty` every minute. This processes jobs (emails, CRM flows) and then **exits**, respecting your hosting's "Max Processes" limit and avoiding timeouts.

3.  **Process Limits Explained**:
    *   The "120 Max Processes" in your plan refers to short-lived scripts (like loading a page).
    *   It does **NOT** allow for "Daemons" (processes that run forever, like `php artisan queue:work` or `reverb:start`).
    *   That's why we use the Scheduler workaround (Step 2). It creates a worker, does the job, and quits immediately. Safe for hosting.

## 9. Final Verification
1.  **VPS**: `docker compose ps` -> all green.
2.  **Hosting**: Check `jobs` table in DB. If empty, the Cron is working!

## 10. Monitoring & Observability (VPS Only)

We have included two powerful tools for monitoring your production VPS.

### A. Portainer (System Levels)
*   **URL**: `http://your-vps-ip:9000`
*   **Purpose**: Monitor CPU, RAM, and Logs of every Docker container.
*   **Setup**: The first time you open the URL, it will ask you to create an Admin password.

### B. Laravel Pulse (Application Levels)
*   **URL**: `http://your-vps-ip/pulse`
*   **Purpose**: Monitor Queue Jobs, Slow Routes, Redis Usage, and Exceptions in real-time.
*   **Access**: Protected by Auth. Login to the Admin Panel (`/admin`) as `admin@admin.com` first, then navigate to `/pulse`.
