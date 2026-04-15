<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'hellocash_invoice_id')) {
                $table->unsignedBigInteger('hellocash_invoice_id')->nullable()->after('invoice_number');
            }
        });

        if (Schema::hasColumn('invoices', 'type')) {
            DB::table('invoices')
                ->where('type', 'cashier')
                ->update(['type' => 'hellocash']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'hellocash_invoice_id')) {
                $table->dropColumn('hellocash_invoice_id');
            }
        });
    }
};
