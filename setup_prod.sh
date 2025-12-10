#!/bin/bash

# setup_prod.sh - Automated Deployment for InmoDB (Docker)

echo "🚀 Starting Deployment Process..."

# 1. Check for .env file
if [ ! -f .env ]; then
    echo "⚠️ .env file not found. Creating from .env.example..."
    cp .env.example .env
    echo "❗ PLEASE EDIT .env NOW! (Set DB_PASSWORD, REDIS_HOST=redis, DB_HOST=db, etc.)"
    echo "Press Enter when ready to continue..."
    read
fi

# 2. Build and Start Containers
echo "🐳 Building and Starting Docker Containers..."
docker compose up -d --build

# 3. Wait for Database
echo "⏳ Waiting for Database to be ready..."
sleep 15

# 4. Run Application Setup
echo "🔧 Running Laravel Setup..."

# Generate Key if not present
if ! grep -q "APP_KEY=base64" .env; then
    docker compose exec app php artisan key:generate
fi

# Run Migrations & Seeders
echo "📦 Migrating Database..."
docker compose exec app php artisan migrate --force

echo "🌱 Seeding Data (if needed)..."
# Check if users table is empty to decide whether to seed
# For simplicity, we run seed, assuming it's idempotent or fresh install. 
# Use --class=DatabaseSeeder to control what runs.
docker compose exec app php artisan db:seed --force

# Link Storage
docker compose exec app php artisan storage:link

# Setup Pulse (Observability)
echo "❤️ Setting up Pulse..."
docker compose exec app php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider" --force
docker compose exec app php artisan migrate --force

# Optimise
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

echo "✅ Deployment Complete!"
echo "--------------------------------------------------------"
echo "🌐 API Endpoint:   http://localhost:80 (or domain)"
echo "💬 Reverb Chat:    ws://localhost:8080"
echo "📊 Portainer UI:   http://localhost:9000"
echo "   - User: Create admin on first login"
echo "❤️ Laravel Pulse:  http://localhost/pulse"
echo "   - Login:        admin@admin.com / password"
echo "--------------------------------------------------------"
