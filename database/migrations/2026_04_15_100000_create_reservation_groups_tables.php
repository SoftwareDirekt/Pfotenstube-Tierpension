<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('total_due', 10, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid / partial / paid
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        Schema::create('reservation_group_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_group_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('overpaid_amount', 10, 2)->nullable();
            $table->string('type');       // advance / final
            $table->string('method');     // Bar / Bank
            $table->text('note')->nullable();
            $table->dateTime('transaction_date');
            $table->string('status')->default('active'); // active / cancelled
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reservation_group_id')->references('id')->on('reservation_groups')->onDelete('cascade');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('reservation_group_id')->nullable()->after('plan_id');
            $table->foreign('reservation_group_id')->references('id')->on('reservation_groups')->onDelete('set null');
        });

        // Allow invoices to link to a group entry instead of (or in addition to) a single reservation
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('reservation_group_id')->nullable()->after('reservation_id');
            $table->unsignedBigInteger('reservation_group_entry_id')->nullable()->after('reservation_group_id');

            $table->foreign('reservation_group_id')->references('id')->on('reservation_groups')->onDelete('set null');
            $table->foreign('reservation_group_entry_id')->references('id')->on('reservation_group_entries')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['reservation_group_entry_id']);
            $table->dropForeign(['reservation_group_id']);
            $table->dropColumn(['reservation_group_entry_id', 'reservation_group_id']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['reservation_group_id']);
            $table->dropColumn('reservation_group_id');
        });

        Schema::dropIfExists('reservation_group_entries');
        Schema::dropIfExists('reservation_groups');
    }
};
