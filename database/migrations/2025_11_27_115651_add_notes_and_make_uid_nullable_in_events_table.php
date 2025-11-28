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
        Schema::table('events', function (Blueprint $table) {
            // Make uid nullable (for Andere type events without employee)
            $table->unsignedBigInteger('uid')->nullable()->change();
            
            // Add notes field after uid (for Andere type descriptions)
            $table->text('notes')->nullable()->after('uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->unsignedBigInteger('uid')->nullable(false)->change();
        });
    }
};
