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
        // Drop tables first
        Schema::dropIfExists('customer_verification_codes');
        Schema::dropIfExists('customer_accounts');

        // Add remote_pfotenstube_homepage_id to tables
        Schema::table('customers', function (Blueprint $table) {
            $table->string('remote_pfotenstube_homepage_id')->nullable()->after('hellocash_customer_id');
        });

        Schema::table('dogs', function (Blueprint $table) {
            $table->string('remote_pfotenstube_homepage_id')->nullable()->after('customer_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('remote_pfotenstube_homepage_id')->nullable()->after('dog_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('remote_pfotenstube_homepage_id');
        });

        Schema::table('dogs', function (Blueprint $table) {
            $table->dropColumn('remote_pfotenstube_homepage_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('remote_pfotenstube_homepage_id');
        });
    }
};
