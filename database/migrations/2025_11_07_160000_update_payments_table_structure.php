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
            if (!Schema::hasColumn('payments', 'plan_cost')) {
                $table->double('plan_cost')->nullable()->after('type');
            }

            if (!Schema::hasColumn('payments', 'remaining_amount')) {
                $table->double('remaining_amount')->nullable()->after('received_amount');
            }
        });

        // Ensure special_cost exists and remains nullable for legacy data
        if (!Schema::hasColumn('payments', 'special_cost')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->double('special_cost')->nullable()->after('plan_cost');
            });
        }

        // Re-order columns and document their purpose
        DB::statement('ALTER TABLE payments MODIFY COLUMN plan_cost DOUBLE NULL AFTER type');
        DB::statement('ALTER TABLE payments MODIFY COLUMN special_cost DOUBLE NULL AFTER plan_cost');
        DB::statement("ALTER TABLE payments MODIFY COLUMN cost DOUBLE NOT NULL COMMENT 'Total cost' AFTER special_cost");
        DB::statement('ALTER TABLE payments MODIFY COLUMN discount INT NOT NULL AFTER cost');
        DB::statement('ALTER TABLE payments MODIFY COLUMN discount_amount DOUBLE NOT NULL AFTER discount');
        DB::statement('ALTER TABLE payments MODIFY COLUMN received_amount DOUBLE NOT NULL AFTER discount_amount');
        DB::statement('ALTER TABLE payments MODIFY COLUMN remaining_amount DOUBLE NULL AFTER received_amount');
        DB::statement('ALTER TABLE payments MODIFY COLUMN status TINYINT NOT NULL COMMENT "0=unpaid, 1=paid, 2=open" AFTER remaining_amount');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'plan_cost')) {
                $table->dropColumn('plan_cost');
            }

            if (Schema::hasColumn('payments', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }
        });

        // Restore original column order and remove the comment from cost
        DB::statement('ALTER TABLE payments MODIFY COLUMN special_cost DOUBLE NULL AFTER cost');
        DB::statement('ALTER TABLE payments MODIFY COLUMN cost DOUBLE NOT NULL');
        DB::statement('ALTER TABLE payments MODIFY COLUMN discount INT NOT NULL AFTER special_cost');
        DB::statement('ALTER TABLE payments MODIFY COLUMN discount_amount DOUBLE NOT NULL AFTER discount');
        DB::statement('ALTER TABLE payments MODIFY COLUMN received_amount DOUBLE NOT NULL AFTER discount_amount');
        DB::statement('ALTER TABLE payments MODIFY COLUMN status TINYINT NOT NULL AFTER received_amount');
    }
};


