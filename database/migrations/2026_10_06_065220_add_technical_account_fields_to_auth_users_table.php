<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            $table->string('account_type', 20)
                ->default('human')
                ->after('email');

            $table->uuid('login_uuid')
                ->nullable()
                ->after('account_type');

            $table->boolean('is_visible')
                ->default(true)
                ->after('is_active');

            $table->unique(
                'login_uuid',
                'uq_auth_users_login_uuid'
            );

            $table->index(
                [
                    'account_type',
                    'is_visible',
                    'is_active',
                ],
                'idx_auth_users_account_visibility'
            );
        });
    }

    public function down(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            $table->dropIndex(
                'idx_auth_users_account_visibility'
            );

            $table->dropUnique(
                'uq_auth_users_login_uuid'
            );

            $table->dropColumn([
                'account_type',
                'login_uuid',
                'is_visible',
            ]);
        });
    }
};