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
        Schema::table('sii_certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('sii_certificates', 'pfx_sha256')) {
                $table->string('pfx_sha256', 64)->nullable()->after('is_default');
            }

            if (!Schema::hasColumn('sii_certificates', 'metadata_hash')) {
                $table->string('metadata_hash', 64)->nullable()->after('pfx_sha256');
            }

            if (!Schema::hasColumn('sii_certificates', 'certificate_fingerprint_sha1')) {
                $table->string('certificate_fingerprint_sha1', 40)->nullable()->after('metadata_hash');
            }

            if (!Schema::hasColumn('sii_certificates', 'has_private_key')) {
                $table->boolean('has_private_key')->default(true)->after('certificate_fingerprint_sha1');
            }

            if (!Schema::hasColumn('sii_certificates', 'last_validity_check_at')) {
                $table->dateTime('last_validity_check_at')->nullable()->after('is_default');
            }

            if (!Schema::hasColumn('sii_certificates', 'last_validity_status')) {
                $table->string('last_validity_status', 30)->nullable()->after('last_validity_check_at');
            }

            $table->index(['company_id', 'is_default'], 'idx_sii_certificates_company_default');
            $table->index(['company_id', 'is_active'], 'idx_sii_certificates_company_active');
            $table->index(['company_id', 'valid_from', 'valid_to'], 'idx_sii_certificates_company_validity');
            $table->index(['company_id', 'pfx_sha256'], 'idx_sii_certificates_company_pfx_sha256');
            $table->index(['company_id', 'metadata_hash'], 'idx_sii_certificates_company_metadata_hash');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sii_certificates', function (Blueprint $table) {
            $table->dropIndex('idx_sii_certificates_company_default');
            $table->dropIndex('idx_sii_certificates_company_active');
            $table->dropIndex('idx_sii_certificates_company_validity');
            $table->dropIndex('idx_sii_certificates_company_pfx_sha256');
            $table->dropIndex('idx_sii_certificates_company_metadata_hash');

            if (Schema::hasColumn('sii_certificates', 'last_validity_status')) {
                $table->dropColumn('last_validity_status');
            }

            if (Schema::hasColumn('sii_certificates', 'last_validity_check_at')) {
                $table->dropColumn('last_validity_check_at');
            }

            if (Schema::hasColumn('sii_certificates', 'has_private_key')) {
                $table->dropColumn('has_private_key');
            }

            if (Schema::hasColumn('sii_certificates', 'certificate_fingerprint_sha1')) {
                $table->dropColumn('certificate_fingerprint_sha1');
            }

            if (Schema::hasColumn('sii_certificates', 'metadata_hash')) {
                $table->dropColumn('metadata_hash');
            }

            if (Schema::hasColumn('sii_certificates', 'pfx_sha256')) {
                $table->dropColumn('pfx_sha256');
            }
        });
    }
};
