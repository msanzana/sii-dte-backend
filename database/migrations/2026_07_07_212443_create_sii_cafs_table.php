<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sii_cafs')) {
            throw new RuntimeException('La tabla sii_cafs no existe. Debes revisar la base actual antes de aplicar este bloque.');
        }

        Schema::table('sii_cafs', function (Blueprint $table) {
            if (!Schema::hasColumn('sii_cafs', 'external_system_id')) {
                $table->unsignedBigInteger('external_system_id')->nullable()->after('company_id');
            }

            if (!Schema::hasColumn('sii_cafs', 'sii_document_type_code')) {
                $table->string('sii_document_type_code', 20)->nullable()->after('external_system_id');
            }

            if (!Schema::hasColumn('sii_cafs', 'branch_office_number')) {
                $table->unsignedInteger('branch_office_number')->nullable()->after('sii_document_type_code');
            }

            if (!Schema::hasColumn('sii_cafs', 'facility_number')) {
                $table->unsignedInteger('facility_number')->nullable()->after('branch_office_number');
            }

            if (!Schema::hasColumn('sii_cafs', 'requested_folios_count')) {
                $table->unsignedInteger('requested_folios_count')->default(0)->after('facility_number');
            }

            if (!Schema::hasColumn('sii_cafs', 'available_folios_count')) {
                $table->unsignedInteger('available_folios_count')->default(0)->after('requested_folios_count');
            }

            if (!Schema::hasColumn('sii_cafs', 'reserved_folios_count')) {
                $table->unsignedInteger('reserved_folios_count')->default(0)->after('available_folios_count');
            }

            if (!Schema::hasColumn('sii_cafs', 'used_folios_count')) {
                $table->unsignedInteger('used_folios_count')->default(0)->after('reserved_folios_count');
            }
        });

        Schema::table('sii_cafs', function (Blueprint $table) {
            $table->index(
                ['company_id', 'external_system_id', 'sii_document_type_code'],
                'idx_sii_cafs_company_system_doc'
            );

            $table->index(
                ['company_id', 'external_system_id', 'sii_document_type_code', 'branch_office_number', 'facility_number'],
                'idx_sii_cafs_distribution'
            );

            $table->foreign('external_system_id')
                ->references('id')
                ->on('external_systems');
        });
    }

    public function down(): void
    {
        Schema::table('sii_cafs', function (Blueprint $table) {
            $table->dropForeign(['external_system_id']);

            $table->dropIndex('idx_sii_cafs_company_system_doc');
            $table->dropIndex('idx_sii_cafs_distribution');

            if (Schema::hasColumn('sii_cafs', 'used_folios_count')) {
                $table->dropColumn('used_folios_count');
            }

            if (Schema::hasColumn('sii_cafs', 'reserved_folios_count')) {
                $table->dropColumn('reserved_folios_count');
            }

            if (Schema::hasColumn('sii_cafs', 'available_folios_count')) {
                $table->dropColumn('available_folios_count');
            }

            if (Schema::hasColumn('sii_cafs', 'requested_folios_count')) {
                $table->dropColumn('requested_folios_count');
            }

            if (Schema::hasColumn('sii_cafs', 'facility_number')) {
                $table->dropColumn('facility_number');
            }

            if (Schema::hasColumn('sii_cafs', 'branch_office_number')) {
                $table->dropColumn('branch_office_number');
            }

            if (Schema::hasColumn('sii_cafs', 'sii_document_type_code')) {
                $table->dropColumn('sii_document_type_code');
            }

            if (Schema::hasColumn('sii_cafs', 'external_system_id')) {
                $table->dropColumn('external_system_id');
            }
        });
    }
};
