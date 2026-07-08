<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('folio_reservation_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('external_system_id');
            $table->unsignedBigInteger('caf_id');

            $table->string('sii_document_type_code', 20);

            $table->unsignedInteger('branch_office_number')->nullable();
            $table->unsignedInteger('facility_number')->nullable();
            $table->string('external_branch_code', 100)->nullable();

            $table->unsignedBigInteger('folio_number');
            $table->unsignedBigInteger('folio_status_id');

            $table->boolean('reserved')->default(false);
            $table->dateTime('reserved_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('used_at')->nullable();

            $table->unsignedBigInteger('dte_document_id')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'external_system_id', 'sii_document_type_code'],
                'idx_folio_details_company_system_doc'
            );

            $table->index(
                ['company_id', 'external_system_id', 'sii_document_type_code', 'branch_office_number', 'facility_number'],
                'idx_folio_details_distribution'
            );

            $table->index(
                ['folio_status_id', 'reserved'],
                'idx_folio_details_status_reserved'
            );

            $table->index(
                ['folio_number'],
                'idx_folio_details_folio_number'
            );

            $table->foreign('folio_reservation_id')
                ->references('id')
                ->on('folio_reservations');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->foreign('external_system_id')
                ->references('id')
                ->on('external_systems');

            $table->foreign('caf_id')
                ->references('id')
                ->on('sii_cafs');

            $table->foreign('folio_status_id')
                ->references('id')
                ->on('folio_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_details');
    }
};
