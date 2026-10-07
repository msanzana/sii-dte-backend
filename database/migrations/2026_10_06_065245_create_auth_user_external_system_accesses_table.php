<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'auth_user_external_system_accesses',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('auth_users')
                    ->cascadeOnDelete();

                $table->foreignId('company_id')
                    ->constrained('companies')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger(
                    'external_system_id'
                );

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();

                $table->unique(
                    [
                        'user_id',
                        'company_id',
                        'external_system_id',
                    ],
                    'uq_auth_user_external_system_access'
                );

                $table->index(
                    [
                        'company_id',
                        'external_system_id',
                        'is_active',
                    ],
                    'idx_auth_user_external_system_active'
                );

                /*
                 * Garantiza físicamente que el sistema externo
                 * pertenezca realmente a la misma empresa.
                 *
                 * Aprovecha el índice único existente:
                 *
                 * external_systems(id, company_id)
                 */
                $table->foreign(
                    [
                        'external_system_id',
                        'company_id',
                    ],
                    'fk_auth_user_external_system_company'
                )
                    ->references([
                        'id',
                        'company_id',
                    ])
                    ->on('external_systems')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'auth_user_external_system_accesses'
        );
    }
};