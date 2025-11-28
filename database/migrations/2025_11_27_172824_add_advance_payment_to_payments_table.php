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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'advance_payment')) {
                $table->double('advance_payment')->default(0)->nullable()->after('remaining_amount');
            }
            if (!Schema::hasColumn('payments', 'wallet_amount')) {
                $table->double('wallet_amount')->default(0)->nullable()->after('advance_payment');
            }
        });

        // Update column order if needed
        DB::statement('ALTER TABLE payments MODIFY COLUMN advance_payment DOUBLE NULL DEFAULT 0 AFTER remaining_amount');
        DB::statement('ALTER TABLE payments MODIFY COLUMN wallet_amount DOUBLE NULL DEFAULT 0 AFTER advance_payment');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'advance_payment')) {
                $table->dropColumn('advance_payment');
            }
            if (Schema::hasColumn('payments', 'wallet_amount')) {
                $table->dropColumn('wallet_amount');
            }
        });
    }
};
