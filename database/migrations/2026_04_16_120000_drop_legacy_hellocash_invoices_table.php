<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HelloCash cashier receipts are stored on `invoices` (see HelloCashService::saveCashierInvoice).
     * The legacy `hellocash_invoices` table and HelloCashInvoice model are no longer used.
     */
    public function up(): void
    {
        Schema::dropIfExists('hellocash_invoices');
    }

    public function down(): void
    {
        // Intentionally empty: do not recreate legacy structure; use backups if rollback is required.
    }
};
