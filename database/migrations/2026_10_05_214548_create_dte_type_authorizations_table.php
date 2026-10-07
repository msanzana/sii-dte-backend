<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Permite crear una FK compuesta que garantice que:
         *
         * external_system_id pertenece realmente a company_id.
         */
        Schema::table('external_systems', function (Blueprint $table) {
            $table->unique(
                ['id', 'company_id'],
                'uq_external_systems_id_company'
            );
        });

        Schema::create('dte_type_authorizations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');

            /*
             * NULL  = autorización a nivel empresa.
             * Valor = autorización específica del sistema externo.
             */
            $table->unsignedBigInteger('external_system_id')
                ->nullable();

            /*
             * Normaliza NULL como 0 para permitir unicidad real
             * empresa + sistema + tipo DTE.
             *
             * Sigue el mismo criterio utilizado en dte_documents
             * para external_system_scope_id.
             */
            $table->unsignedBigInteger('external_system_scope_id')
                ->storedAs('COALESCE(external_system_id, 0)');

            $table->unsignedBigInteger('dte_document_type_id');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('authorized_at')
                ->nullable();

            $table->timestamp('deactivated_at')
                ->nullable();

            /*
             * Reservado para parámetros particulares de autorización,
             * límites o comportamiento futuro por empresa/sistema.
             */
            $table->json('parameters')
                ->nullable();

            $table->timestamps();

            /*
             * Una sola definición por:
             *
             * empresa + sistema lógico + tipo DTE
             */
            $table->unique(
                [
                    'company_id',
                    'external_system_scope_id',
                    'dte_document_type_id',
                ],
                'uq_dte_type_auth_company_system_type'
            );

            $table->index(
                [
                    'company_id',
                    'is_active',
                ],
                'idx_dte_type_auth_company_active'
            );

            $table->index(
                [
                    'external_system_id',
                    'is_active',
                ],
                'idx_dte_type_auth_system_active'
            );

            /*
             * Empresa.
             */
            $table->foreign(
                'company_id',
                'fk_dte_type_auth_company'
            )
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();

            /*
             * Tipo DTE paramétrico.
             */
            $table->foreign(
                'dte_document_type_id',
                'fk_dte_type_auth_document_type'
            )
                ->references('id')
                ->on('dte_document_types')
                ->restrictOnDelete();

            /*
             * Esta FK compuesta impide, por ejemplo:
             *
             * company_id = 1
             * external_system_id perteneciente a company_id = 2
             */
            $table->foreign(
                [
                    'external_system_id',
                    'company_id',
                ],
                'fk_dte_type_auth_external_system_company'
            )
                ->references([
                    'id',
                    'company_id',
                ])
                ->on('external_systems')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_type_authorizations');

        Schema::table('external_systems', function (Blueprint $table) {
            $table->dropUnique(
                'uq_external_systems_id_company'
            );
        });
    }
};