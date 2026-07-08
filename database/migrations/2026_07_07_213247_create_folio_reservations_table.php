<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_reservations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('caf_id');
            $table->unsignedBigInteger('external_system_id');
            $table->unsignedBigInteger('company_id');

            $table->string('sii_document_type_code', 20);

            $table->unsignedInteger('branch_office_number')->nullable();
            $table->unsignedInteger('facility_number')->nullable();
            $table->string('external_branch_code', 100)->nullable();

            $table->unsignedBigInteger('folio_range_from');
            $table->unsignedBigInteger('folio_range_to');
            $table->unsignedBigInteger('current_folio')->nullable();

            $table->unsignedInteger('reserved_quantity')->default(0);

            $table->dateTime('reserved_at');
            $table->dateTime('expires_at')->nullable();

            $table->boolean('is_currently_valid')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['company_id', 'external_system_id', 'sii_document_type_code'],
                'idx_folio_reservations_company_system_doc'
            );

            $table->index(
                ['company_id', 'external_system_id', 'sii_document_type_code', 'branch_office_number', 'facility_number'],
                'idx_folio_reservations_distribution'
            );

            $table->index(
                ['company_id', 'is_currently_valid', 'is_active'],
                'idx_folio_reservations_valid_active'
            );

            $table->foreign('caf_id')
                ->references('id')
                ->on('sii_cafs');

            $table->foreign('external_system_id')
                ->references('id')
                ->on('external_systems');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_reservations');
    }
};
