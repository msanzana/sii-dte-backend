<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'auth_user_external_system_accesses',
            function (Blueprint $table) {
                $table->foreign(
                    [
                        'user_id',
                        'company_id',
                    ],
                    'fk_auth_user_external_system_company_access'
                )
                    ->references([
                        'user_id',
                        'company_id',
                    ])
                    ->on('auth_user_company_accesses')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'auth_user_external_system_accesses',
            function (Blueprint $table) {
                $table->dropForeign(
                    'fk_auth_user_external_system_company_access'
                );
            }
        );
    }
};