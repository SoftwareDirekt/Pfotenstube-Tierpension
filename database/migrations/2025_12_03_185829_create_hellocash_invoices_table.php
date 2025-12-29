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
        Schema::create('hellocash_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hellocash_invoice_id')->unique();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->index('hellocash_invoice_id');
            $table->index('reservation_id');
            $table->index('payment_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hellocash_invoices');
    }
};
