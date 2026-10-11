<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_refresh_tokens', function (Blueprint $table): void {
            $table->unsignedBigInteger('external_system_id')
                ->nullable()
                ->after('company_id');

            $table->index(
                ['external_system_id', 'company_id'],
                'idx_auth_refresh_tokens_external_system_company'
            );

            $table->foreign(
                ['external_system_id', 'company_id'],
                'fk_auth_refresh_tokens_external_system_company'
            )
                ->references(['id', 'company_id'])
                ->on('external_systems');
        });
    }

    public function down(): void
    {
        Schema::table('auth_refresh_tokens', function (Blueprint $table): void {
            $table->dropForeign('fk_auth_refresh_tokens_external_system_company');
            $table->dropIndex('idx_auth_refresh_tokens_external_system_company');
            $table->dropColumn('external_system_id');
        });
    }
};
