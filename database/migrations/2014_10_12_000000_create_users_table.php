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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->nullable();
            $table->string('department')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('type', [1,2,3,4])->default(1)->comment('1: technician, 2: service center, 3: dispatch, 4: manager');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->integer('status')->default(1)->comment('1:active, 2:inactive');
            $table->string('picture')->nullable();
            $table->text('permissions')->nullable();
            $table->rememberToken();
            $table->tinyInteger('role')->default(2)->comment('1: admin, 2: employee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
