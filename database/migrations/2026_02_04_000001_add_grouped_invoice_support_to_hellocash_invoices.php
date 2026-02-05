<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hellocash_invoices', function (Blueprint $table) {
            // Add grouped invoice support
            if (!Schema::hasColumn('hellocash_invoices', 'is_grouped')) {
                $table->boolean('is_grouped')->default(false)->after('payment_id')->comment('Flag for grouped invoices (multiple reservations)');
            }
            
            if (!Schema::hasColumn('hellocash_invoices', 'reservation_ids')) {
                $table->json('reservation_ids')->nullable()->after('is_grouped')->comment('Array of reservation IDs for grouped invoices');
            }
            
            if (!Schema::hasColumn('hellocash_invoices', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('reservation_ids');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
                $table->index('customer_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hellocash_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('hellocash_invoices', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            }
            
            if (Schema::hasColumn('hellocash_invoices', 'reservation_ids')) {
                $table->dropColumn('reservation_ids');
            }
            
            if (Schema::hasColumn('hellocash_invoices', 'is_grouped')) {
                $table->dropColumn('is_grouped');
            }
        });
    }
};
