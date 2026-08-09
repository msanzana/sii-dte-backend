<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folio_details', function (Blueprint $table) {
            if (!Schema::hasColumn('folio_details', 'expired_at')) {
                $table->dateTime('expired_at')
                    ->nullable()
                    ->after('used_at');

                $table->index(
                    'expired_at',
                    'idx_folio_details_expired_at'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('folio_details', function (Blueprint $table) {
            if (Schema::hasColumn('folio_details', 'expired_at')) {
                $table->dropIndex(
                    'idx_folio_details_expired_at'
                );

                $table->dropColumn('expired_at');
            }
        });
    }
};