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
        Schema::table('payments', function (Blueprint $table) {
            // Add invoice_id to link payments to invoices
            if (!Schema::hasColumn('payments', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('res_id');
                $table->foreign('invoice_id')->references('id')->on('hellocash_invoices')->onDelete('set null');
                $table->index('invoice_id');
            }

            // Add days for invoice display
            if (!Schema::hasColumn('payments', 'days')) {
                $table->integer('days')->nullable()->after('special_cost')->comment('Number of days for this reservation');
            }

            // Add net_amount for invoice display
            if (!Schema::hasColumn('payments', 'net_amount')) {
                $table->decimal('net_amount', 10, 2)->nullable()->after('vat_amount')->comment('Net amount (cost - vat_amount)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'net_amount')) {
                $table->dropColumn('net_amount');
            }

            if (Schema::hasColumn('payments', 'days')) {
                $table->dropColumn('days');
            }

            if (Schema::hasColumn('payments', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropIndex(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });
    }
};
