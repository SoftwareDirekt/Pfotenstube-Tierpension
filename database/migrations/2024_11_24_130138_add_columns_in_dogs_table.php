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
        Schema::table('dogs', function (Blueprint $table) {
            $table->tinyInteger('is_allergy')->default(0)->comment('0,1')->after('age');
            $table->string('allergy')->nullable()->after('is_allergy');

            $table->tinyInteger('is_eating_habits')->default(0)->comment('0,1')->after('water_lover');
            $table->string('eating_morning')->nullable()->after('is_eating_habits');
            $table->string('eating_midday')->nullable()->after('eating_morning');
            $table->string('eating_evening')->nullable()->after('eating_midday');

            $table->tinyInteger('is_special_eating')->default(0)->comment('0,1')->after('eating_evening');
            $table->string('special_morning')->nullable()->after('is_special_eating');
            $table->string('special_midday')->nullable()->after('special_morning');
            $table->string('special_evening')->nullable()->after('special_midday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            //
        });
    }
};
