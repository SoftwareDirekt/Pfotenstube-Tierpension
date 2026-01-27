<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'Rechnungen' page if it doesn't exist
        $exists = DB::table('pages')->where('name', 'Rechnungen')->exists();
        
        if (!$exists) {
            DB::table('pages')->insert([
                'name' => 'Rechnungen',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'Rechnungen' page
        DB::table('pages')->where('name', 'Rechnungen')->delete();
    }
};
