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
        Schema::table('folio_reservations', function (Blueprint $table) {
            $table->dateTime('deactivated_at')
                ->nullable()
                ->after('is_active');
            $table->unsignedBigInteger('deactivated_by_user_id')
                ->nullable()
                ->after('is_active');
            $table->string('deactivation_source',40)
                ->nullable()
                ->after('deactivated_by_user_id');
            $table->text('deactivation_reason')
                ->nullable()
                ->after('deactivation_source');
            $table->index(
                [
                    'is_active',
                    'is_currently_valid',
                    'expires_at',
                ],
                'idx_folio_reservations_expiration_scan'
            );
            $table->foreign(
                'deactivated_by_user_id',
                'fk_folio_reservations_deactivated_by'
            )
                ->references('id')
                ->on('auth_users')
                ->nullOnDelete();
        });

        Schema::table('folio_details', function (Blueprint $table){
            $table->dateTime('expired_at')
                ->nullable()
                ->after('user_id');
            $table->index(
                ['folio_reservation_id', 'expired_at'],
                'idx_folio_details_reservation_expired'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folio_details', function (Blueprint $table) {
            $table->dropForeign(
                'fk_folio_details_reservation_expired'
            );
            $table->dropColumn('expired_at');
        });

        Schema::table('folio_reservations', function (Blueprint $table){
            $table->dropForeign(
                'fk_folio_reservations_deactivated_by'
            );
            $table->dropIndex(
                'idx_folio_reservations_expiration_scan'
            );
            $table->dropColumn([
                'deactivated_at',
                'deactivated_by_user_id',
                'deactivation_source',
                'deactivation_reason',
            ]);
        });
    }
};
