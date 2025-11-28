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
        DB::transaction(function () {
            DB::table('payments')
                ->where('status', 1)
                ->update(['status' => 3]);

            DB::table('payments')
                ->where('status', 2)
                ->update(['status' => 1]);

            DB::table('payments')
                ->where('status', 3)
                ->update(['status' => 2]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            DB::table('payments')
                ->where('status', 2)
                ->update(['status' => 3]);

            DB::table('payments')
                ->where('status', 1)
                ->update(['status' => 2]);

            DB::table('payments')
                ->where('status', 3)
                ->update(['status' => 1]);
        });
    }
};
