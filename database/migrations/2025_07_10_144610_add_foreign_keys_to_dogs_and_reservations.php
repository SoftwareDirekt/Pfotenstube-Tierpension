<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('dogs', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->change();
        });

        $hasDogsFK = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'dogs')
            ->where('CONSTRAINT_NAME', 'dogs_customer_id_foreign')
            ->exists();

        if ($hasDogsFK) {
            Schema::table('dogs', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }

        Schema::table('dogs', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->onDelete('cascade');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('dog_id')->change();
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });

        $hasDogResFK = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'reservations')
            ->where('CONSTRAINT_NAME', 'reservations_dog_id_foreign')
            ->exists();

        if ($hasDogResFK) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['dog_id']);
            });
        }

        $hasRoomResFK = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'reservations')
            ->where('CONSTRAINT_NAME', 'reservations_room_id_foreign')
            ->exists();

        if ($hasRoomResFK) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['room_id']);
            });
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('dog_id')
                ->references('id')->on('dogs')
                ->onDelete('cascade');

            $table->foreign('room_id')
                ->references('id')->on('rooms')
                ->onDelete('set null');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropForeign(['dog_id']);
        });

        Schema::table('dogs', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
