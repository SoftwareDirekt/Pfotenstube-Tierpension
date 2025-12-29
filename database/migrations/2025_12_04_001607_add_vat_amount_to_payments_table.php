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
            if (!Schema::hasColumn('payments', 'vat_amount')) {
                $table->decimal('vat_amount', 10, 2)->default(0)->nullable()->after('cost')->comment('VAT amount when HelloCash is used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });
    }
};
