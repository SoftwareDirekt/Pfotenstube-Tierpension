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
            $table->dropUnique(['hellocash_invoice_id']);
        });
        
        Schema::table('hellocash_invoices', function (Blueprint $table) {
            // 'cashier' = HelloCash invoices, 'local' = Bank transfer invoices
            $table->string('invoice_type')->default('cashier')->after('id'); // 'cashier' or 'local'
            $table->unsignedBigInteger('invoice_number')->nullable()->after('invoice_type'); // For local invoices (UEB-01, UEB-02, etc.)
            $table->unsignedBigInteger('hellocash_invoice_id')->nullable()->change(); // For cashier invoices only
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hellocash_invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_type', 'invoice_number']);
            $table->unsignedBigInteger('hellocash_invoice_id')->nullable(false)->change();
            $table->unique('hellocash_invoice_id');
        });
    }
};
