<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_care_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            /** draft | intake_signed | completed */
            $table->string('status', 32)->default('draft');
            $table->text('besonderheiten')->nullable();
            $table->json('care_options');
            $table->string('intake_signature_path')->nullable();
            $table->string('checkout_signature_path')->nullable();
            $table->string('final_pdf_path')->nullable();
            $table->timestamp('intake_signed_at')->nullable();
            $table->timestamp('checkout_signed_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->unique('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_care_agreements');
    }
};
