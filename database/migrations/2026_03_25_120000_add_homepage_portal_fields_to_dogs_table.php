<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            $table->boolean('chip_not_applicable')->default(false)->after('chip_number');
            $table->string('vaccine_pass_page1')->nullable()->after('chip_not_applicable');
            $table->string('vaccine_pass_page2')->nullable()->after('vaccine_pass_page1');
        });
    }

    public function down(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            $table->dropColumn(['chip_not_applicable', 'vaccine_pass_page1', 'vaccine_pass_page2']);
        });
    }
};
