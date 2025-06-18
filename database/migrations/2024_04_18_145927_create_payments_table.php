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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('res_id');
            $table->string('type');
            $table->double('cost');
            $table->double('received_amount');
            $table->Integer('discount');
            $table->double('discount_amount');
            $table->tinyInteger('status')->default(0)->comment('0=unpaid, 2=received 1=paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
