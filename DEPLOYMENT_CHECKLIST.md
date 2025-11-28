# Post-Deployment Commands

## Step 1: Run Database Migrations
```bash
php artisan migrate
```

## Step 2: Run Legacy Data Migration Commands
```bash
# 1. Fix legacy payment settlements (rebuild settlement audit trail)
php artisan payments:fix-legacy-settlements --dry-run
php artisan payments:fix-legacy-settlements

# 2. Reconcile customer balances (ensure balances match calculations)
php artisan customers:reconcile-balances --dry-run
php artisan customers:reconcile-balances

# 3. Calculate legacy visit counts
php artisan visits:calculate-legacy --dry-run
php artisan visits:calculate-legacy

# 4. Update legacy event colors
php artisan events:update-legacy-colors
```

## Step 3: Clear and Cache for Production
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 4: Verify Deployment
- Check Laravel logs: `storage/logs/laravel.log`
- Test critical flows (checkout, payment, balance updates)
- Verify customer balances are correct
- Check payment settlements
