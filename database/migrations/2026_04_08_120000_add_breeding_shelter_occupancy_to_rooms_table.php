<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('breeding_shelter_occupancy')->default(0)->after('capacity');
            $table->longText('breeding_shelter_animals')->nullable()->after('breeding_shelter_occupancy');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('breeding_shelter_occupancy');
            $table->dropColumn('breeding_shelter_animals');
        });
    }
};
