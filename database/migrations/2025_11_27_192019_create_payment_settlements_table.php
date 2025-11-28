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
        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settling_payment_id')->comment('The payment that is settling the debt');
            $table->unsignedBigInteger('settled_payment_id')->comment('The old payment whose debt is being settled');
            $table->double('amount_settled')->comment('Amount used to settle the old payment debt');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('settling_payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('settled_payment_id')->references('id')->on('payments')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('settling_payment_id');
            $table->index('settled_payment_id');
            
            // Prevent duplicate settlements (same payment settling same debt twice)
            // Note: This allows same settling payment to settle multiple old payments
            // but prevents duplicate settlements for the same pair
            $table->unique(['settling_payment_id', 'settled_payment_id'], 'unique_settlement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settlements');
    }
};
