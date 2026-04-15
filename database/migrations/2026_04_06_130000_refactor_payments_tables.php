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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('payment_settlements');
        Schema::dropIfExists('payments');

        Schema::enableForeignKeyConstraints();

        Schema::create('reservation_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('res_id');
            $table->decimal('total_due', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('res_id')->references('id')->on('reservations')->onDelete('cascade');
        });

        Schema::create('reservation_payment_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('res_payment_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('overpaid_amount', 10, 2)->nullable();
            $table->string('type');
            $table->string('method');
            $table->text('note')->nullable();
            $table->dateTime('transaction_date');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('res_payment_id')->references('id')->on('reservation_payments')->onDelete('cascade');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('res_payment_entry_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('type')->default('final');
            $table->string('file_path')->nullable();
            $table->string('status')->default('paid');
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('res_payment_entry_id')->references('id')->on('reservation_payment_entries')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        Schema::create('reservation_additional_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('additional_cost_id')->nullable();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
            $table->foreign('additional_cost_id')->references('id')->on('additional_costs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_additional_costs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('reservation_payment_entries');
        Schema::dropIfExists('reservation_payments');
    }
};
