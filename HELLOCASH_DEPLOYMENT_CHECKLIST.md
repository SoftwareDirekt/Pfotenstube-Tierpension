# HelloCash Integration Deployment Checklist

This checklist covers the deployment steps required after integrating HelloCash invoice system into the application. It includes both HelloCash-specific setup and legacy data migration commands.

**Quick Reference:** Follow steps 1-9 in order. Steps 4 (Queue Worker) must be completed before Step 5 (Customer Sync). Step 6 (Cache) should be done last after all configuration is complete.

## Overview

This deployment includes:
- HelloCash API integration for invoice generation (NEW feature)
- Background job processing for customer synchronization
- Legacy data migration (payments, balances, visits, events)
- Queue worker setup for production

**Important Notes:**
- **Invoices are a NEW feature** - the previous system only had payments and settlements, no invoices
- All invoices are generated via HelloCash after deployment
- All checkout processes will automatically create HelloCash invoices
- Legacy migration commands only handle payments, balances, visits, and events (NOT invoices)

---

## Prerequisites

Before starting deployment, ensure:
- [ ] Database backup has been created
- [ ] Application is in maintenance mode (if applicable)
- [ ] HelloCash API credentials are available
- [ ] Access to Cloudways platform for supervisor configuration
- [ ] API testing tool (Postman or similar) for dispatching sync job

---

## Step 1: Environment Configuration

### 1.1 HelloCash API Configuration

Add the following to your `.env` file:

```bash
# HelloCash API Configuration
HELLOCASH_API_KEY=your_api_key_here
HELLOCASH_BASE_URL=https://api.hellocash.business/api/v1
HELLOCASH_SIGNATURE_MANDATORY=false # For production: Set to true only if you have a signature already setup in hellocash system otherwise the invoice generation wont work.
HELLOCASH_TEST_MODE=true  # Set to false for production
```

**⚠️ Important:** 
- Start with `HELLOCASH_TEST_MODE=true` for initial testing
- Only set to `false` after verifying everything works correctly
- Keep your API key secure and never commit it to version control

### 1.2 Queue Configuration

**Required for HelloCash customer sync job:**

```bash
# Production queue configuration (will be set to 'redis' in Step 4)
QUEUE_CONNECTION=database  # Use 'sync' for local development
```

**Note:** 
- For production, this will be changed to `redis` in Step 4 after supervisor is configured
- The `database` queue driver requires the `jobs` table, which will be created in Step 2

### 1.3 VAT Percentage Preference

Set the VAT percentage (if not already configured):

**Option A: Via Admin Panel**
- Navigate to `/admin/settings` (Preferences tab)
- Set `vat_percentage` value

**Option B: Via Database**
```sql
INSERT INTO preferences (key, value, type) VALUES ('vat_percentage', '20', 'float')
ON DUPLICATE KEY UPDATE value = '20';
```

**Default:** 20% (Austrian standard VAT rate)

---

## Step 2: Run Database Migrations

```bash
php artisan migrate
```

**What this does:**
- Creates the `jobs` table (required for queue processing)
- Creates the `hellocash_invoices` table (stores invoice metadata)
- Creates any other pending migrations
- Adds `hellocash_customer_id` column to `customers` table (if migration exists)

**⚠️ Important:** 
- Always run migrations in a maintenance window
- The `jobs` table is essential for the HelloCash customer sync background job

---

## Step 3: Run Legacy Data Migration Commands

These commands migrate and fix legacy data (payments, balances, visits, events) to ensure consistency with the new system.

**Note:** These commands handle legacy payments and settlements. Invoices are a NEW feature introduced with HelloCash integration - there are no legacy invoices to migrate.

### 3.1 Fix Legacy Payment Settlements

Rebuilds settlement audit trail for legacy payments:

```bash
# Preview changes first (recommended)
php artisan payments:fix-legacy-settlements --dry-run

# Apply changes
php artisan payments:fix-legacy-settlements
```

**What this does:**
- Recalculates `remaining_amount` and `advance_payment` for legacy payments
- Creates settlement records for audit trail
- Processes payments chronologically per customer

### 3.2 Reconcile Customer Balances

Ensures customer balances match calculations:

```bash
# Preview changes first (recommended)
php artisan customers:reconcile-balances --dry-run

# Apply changes
php artisan customers:reconcile-balances
```

**What this does:**
- Recalculates customer balances from payment history
- Updates balance fields to match actual calculations
- Identifies and reports discrepancies

### 3.3 Calculate Legacy Visit Counts

Populates visit/day counts for legacy records:

```bash
# Preview changes first (recommended)
php artisan visits:calculate-legacy --dry-run

# Apply changes
php artisan visits:calculate-legacy
```

**What this does:**
- Calculates total visits from checked-out reservations
- Calculates total days from reservation history
- Updates `visits` and `days` fields in the `dogs` table

### 3.4 Update Legacy Event Colors

Updates event colors for legacy events:

```bash
php artisan events:update-legacy-colors
```

**Note:** This command does not have a dry-run option as it's a safe operation.

---

## Step 4: Setup Queue Worker on Cloudways

The queue worker is **required** for the HelloCash customer sync job to process in the background.

### 4.1 Enable Supervisor from Cloudways Server

1. Log in to Cloudways Platform
2. Navigate to **Application Management** → **Application Settings**
3. Go to **Supervisord Jobs** section
4. Click **Add New Job** (use default settings)
5. Change **Tries** to `3`
6. Create the job without changing any other settings

### 4.2 Configure Queue Connection

Update your `.env` file to use Redis for queue processing:

```bash
# Change queue connection to Redis
QUEUE_CONNECTION=redis
```

**⚠️ Important:** 
- The queue worker must be running before dispatching the customer sync job in Step 5
- After creating the supervisor job and updating `.env`, the job will be automatically handled by supervisor when dispatched via Postman

### 4.3 Verify Queue Worker Status

**Via Cloudways Platform:**
- Check supervisor process status in Cloudways Platform → Application Settings → Supervisord Jobs section
- Verify the process shows as "Running" status

**Via Database:**
```sql
-- Check if jobs are being processed (jobs should decrease over time)
SELECT COUNT(*) as pending_jobs FROM jobs;
```
---

## Step 5: Dispatch HelloCash Customer Sync Job

This step syncs existing customers to HelloCash (one-time operation).

**⚠️ Prerequisite:** Ensure Step 4 (Queue Worker) is completed and the worker is running before proceeding.

### 5.1 Dispatch Sync Job

**Method: Via API Route (Postman or Similar)**

Use Postman or a similar API testing tool to dispatch the sync job:

**Request Details:**
- **Method:** `POST`
- **URL:** `https://your-domain.com/api/hellocash/sync-customers`
- **Headers:** 
  - `Content-Type: application/json`
  - `Authorization: Bearer YOUR_TOKEN` (if authentication is required)

**Example using curl:**
```bash
curl -X POST https://your-domain.com/api/hellocash/sync-customers \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "message": "HelloCash customer sync job has been dispatched. Check logs for progress."
}
```

**Note:** 
- The endpoint will return a JSON response confirming the job has been dispatched
- Monitor the logs to track sync progress
- The job processes customers in batches of 30 (configurable) at a rate of 10 per minute
- The job automatically continues processing remaining customers in subsequent batches

### 5.2 Monitor Sync Progress

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor worker logs
tail -f storage/logs/worker.log

# Check queue status (via database)
# Connect to database and run:
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
```

### 5.3 Verify Sync Completion

```sql
-- Check how many customers are synced
SELECT 
    COUNT(*) as total_customers,
    COUNT(hellocash_customer_id) as synced_customers,
    COUNT(*) - COUNT(hellocash_customer_id) as unsynced_customers
FROM customers;

-- List unsynced customers (if any)
SELECT id, name, email, created_at 
FROM customers 
WHERE hellocash_customer_id IS NULL;
```

**Note:** 
- The sync job processes customers in batches of 30 (configurable) at a rate of 10 per minute
- Each batch processes within timeout limits and automatically dispatches the next batch
- Failed customers will be logged and can be retried
- New customers created after deployment are automatically synced on creation
- The job will automatically resume from where it left off if interrupted (skips already-synced customers)

---

## Step 6: Clear and Cache for Production

Optimize the application for production. **Run this step after all previous steps are completed.**

**⚠️ Important:** 
- Only run cache commands after all configuration (Steps 1-5) is complete
- This ensures cached config includes all new settings

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

**What this does:**
- Clears old cached configuration and routes
- Caches optimized configuration for production performance
- Improves application response times

---

## Step 7: Verify Deployment

### 7.1 Check Application Logs

```bash
# View recent logs
tail -n 100 storage/logs/laravel.log

# Watch logs in real-time
tail -f storage/logs/laravel.log
```

Look for:
- ✅ No critical errors
- ✅ HelloCash API connection successful
- ✅ Queue jobs processing correctly

### 7.2 Test Critical Flows

- [ ] **Customer Creation:** Create a new customer and verify it syncs to HelloCash
- [ ] **Checkout Process:** Complete a reservation checkout and verify:
  - Invoice is created in HelloCash
  - Invoice PDF is saved to storage
  - Invoice record is created in database
- [ ] **Payment Processing:** Verify payment settlements work correctly
- [ ] **Balance Updates:** Verify customer balances update correctly
- [ ] **Invoice Viewing:** Test invoice listing and download at `/admin/invoices`

### 7.3 Verify Data Integrity

- [ ] Customer balances are correct
- [ ] Payment settlements are accurate
- [ ] Visit counts are correct
- [ ] Event colors are updated

---

## Step 8: HelloCash Integration Verification

### 8.1 API Configuration Verification

- [ ] HelloCash API credentials are configured correctly
- [ ] Test mode is set appropriately (`true` for testing, `false` for production)
- [ ] API connection test successful (check logs)

### 8.2 Invoice Generation Testing

- [ ] **Test Mode:** Create test invoice with `HELLOCASH_TEST_MODE=true`
  - Verify invoice appears in HelloCash test environment
  - Verify invoice PDF is generated and saved
  - Verify invoice metadata is stored in database

- [ ] **Production Mode:** After testing, switch to `HELLOCASH_TEST_MODE=false`
  - Verify invoice appears in HelloCash production environment
  - Test with real checkout process

### 8.3 Invoice Storage Verification

- [ ] Invoice PDFs are stored in `storage/app/invoices/YYYY/MM/`
- [ ] Invoice listing page works at `/admin/invoices`
- [ ] Invoice viewing and downloading functions correctly
- [ ] Invoice records are linked to reservations and payments

### 8.4 VAT Configuration

- [ ] VAT percentage is set in preferences (default: 20%)
- [ ] VAT is correctly calculated in invoice items
- [ ] Net and gross prices are calculated correctly

### 8.5 Customer Sync Verification

- [ ] Queue worker is running and processing jobs
- [ ] Existing customers have `hellocash_customer_id` populated
- [ ] New customers are automatically synced on creation
- [ ] Failed syncs are logged and can be retried

**Check sync status:**
```sql
-- Verify customer sync
SELECT 
    COUNT(*) as total,
    COUNT(hellocash_customer_id) as synced,
    COUNT(*) - COUNT(hellocash_customer_id) as pending
FROM customers;
```

---

## Step 9: Post-Deployment Monitoring

### 9.1 Monitor Queue Processing

```bash
# Check queue status
SELECT COUNT(*) as pending_jobs FROM jobs;

# Check failed jobs
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

### 9.2 Monitor HelloCash API Usage

- Check HelloCash dashboard for API usage
- Monitor rate limits (default: 10 requests/minute for sync)
- Review any API errors in logs

### 9.3 Monitor Application Performance

- Check response times for checkout process
- Monitor queue worker performance
- Review error rates in logs

---

## Troubleshooting

### Queue Worker Not Processing Jobs

**Symptoms:** Jobs remain in `jobs` table, not being processed

**Solutions:**
1. Check queue worker status via Cloudways Platform → Application Settings → Supervisord Jobs section
2. Restart worker via Cloudways Platform UI (stop and start the supervisor job)
3. Verify `QUEUE_CONNECTION=redis` in `.env`
4. Verify supervisor job is configured correctly in Cloudways Platform (tries set to 3)
5. Clear config cache: `php artisan config:clear` after changing queue connection

### HelloCash API Errors

**Symptoms:** Invoice creation fails, API errors in logs

**Solutions:**
1. Verify API key is correct in `.env`
2. Check API base URL is correct
3. Verify test mode setting matches your HelloCash environment
4. Check rate limits (reduce sync rate if needed)
5. Review HelloCash API documentation for error codes

### Customer Sync Failures

**Symptoms:** Some customers not syncing to HelloCash

**Solutions:**
1. Check logs for specific error messages
2. Verify customer data is complete (name, email, etc.)
3. Retry sync job by dispatching again via API route: `POST /api/hellocash/sync-customers`
4. Check HelloCash API for duplicate email errors
5. Review failed jobs table: `SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;`

### Invoice PDF Not Saving

**Symptoms:** Invoice created in HelloCash but PDF not saved locally

**Solutions:**
1. Check storage permissions: `storage/app/invoices/`
2. Verify disk space available
3. Check Laravel logs for storage errors
4. Verify `file_path` column in `hellocash_invoices` table

---

## Rollback Plan

If issues occur, you can rollback by:

1. **Disable HelloCash Integration:**
   - Set `HELLOCASH_TEST_MODE=true` (or remove config)
   - System will continue to work without HelloCash

2. **Revert Queue Configuration:**
   - Set `QUEUE_CONNECTION=sync` in `.env`
   - Stop queue worker via Cloudways Platform → Application Settings → Supervisor section

3. **Database Rollback:**
   - Restore from backup if needed
   - Run: `php artisan migrate:rollback` (if migrations need reversal)

**Note:** Legacy data migration commands are generally safe and don't require rollback, but you can restore from database backup if needed.

---

## Additional Notes

### Invoice Generation (New Feature)

- **Invoices are a NEW feature** introduced with HelloCash integration
- The previous system only had payments and settlements - no invoices existed
- All invoices are generated via HelloCash during checkout process
- Invoice PDFs are stored locally in `storage/app/invoices/YYYY/MM/`
- Invoice metadata is stored in `hellocash_invoices` table
- Invoices are linked to reservations and payments

### New Customer Auto-Sync

- New customers created after deployment are **automatically synced** to HelloCash on creation
- No manual intervention required for new customers
- Sync happens synchronously during customer creation

### Maintenance

- Queue worker should run continuously in production
- Monitor queue worker logs regularly
- Review HelloCash API usage periodically
- Keep HelloCash API credentials secure and rotate if needed

---

## Deployment Sign-Off

After completing all steps, verify:

- [ ] All environment variables are configured
- [ ] Database migrations completed successfully
- [ ] Legacy data migration commands executed
- [ ] Queue worker is running and processing jobs
- [ ] Customer sync completed (or in progress)
- [ ] Application caches cleared and optimized
- [ ] All verification tests passed
- [ ] Monitoring and logging are working
- [ ] Team is notified of deployment completion

**Deployment Date:** 19-December-2025

**Deployed By:** Anas Khalid (https://github.com/anaskld)

**Verified By:** Anas Khalid (https://github.com/anaskld)

---

## Support & Documentation

- HelloCash API Documentation: https://api.hellocash.business/docs
- Laravel Queue Documentation: https://laravel.com/docs/queues
- Cloudways Supervisor Guide: Cloudways Platform Documentation

For issues or questions, check:
- Application logs: `storage/logs/laravel.log`
- Worker logs: `storage/logs/worker.log`
- Queue status: `SELECT * FROM jobs;`
- Failed jobs: `SELECT * FROM failed_jobs;`
