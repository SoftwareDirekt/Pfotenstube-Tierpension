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
        Schema::create('dogs', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->string('name');
            $table->string('picture')->default('no-user-picture.gif');
            $table->string('age')->nullable();
            $table->tinyInteger('neutered')->default(0)->comment('0: No, 1: Yes');
            $table->string('compatible_breed')->nullable();
            $table->string('chip_number')->nullable();
            $table->tinyInteger('is_medication')->default(0)->comment('0: No, 1: Yes');
            $table->string('medication')->nullable();
            $table->string('health_problems')->nullable();
            $table->string('eating_habits')->nullable();
            $table->string('morgen')->nullable();
            $table->string('mittag')->nullable();
            $table->string('abend')->nullable();
            $table->string('compatibility')->nullable();
            $table->tinyInteger('water_lover')->default(0)->comment('0: No, 1: Yes');
            $table->tinyInteger('status')->default(1)->comment('1: alive, 2: conveyed, 3: died');
            $table->string('died')->nullable();
            $table->string('adopt_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('weight')->nullable();
            $table->text('note')->nullable();
            $table->integer('reg_plan')->nullable();
            $table->integer('day_plan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dogs');
    }
};
