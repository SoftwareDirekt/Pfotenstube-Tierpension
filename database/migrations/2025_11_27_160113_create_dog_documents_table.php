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
        Schema::create('dog_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dog_id');
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable(); // Size in bytes
            $table->text('description')->nullable();
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('dog_id')->references('id')->on('dogs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dog_documents');
    }
};
