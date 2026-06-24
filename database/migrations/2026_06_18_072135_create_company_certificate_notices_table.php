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
        Schema::create('company_certificate_notices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('certificate_id')->nullable();

            $table->string('source', 20)->default('automatic');
            $table->string('type', 20)->default('info');
            $table->string('code', 80)->nullable();
            $table->string('title', 150);
            $table->text('message');

            $table->date('notice_date');
            $table->time('notice_time');
            $table->dateTime('emitted_at');

            $table->boolean('is_read')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('user_id')->references('id')->on('auth_users');
            $table->foreign('certificate_id')->references('id')->on('sii_certificates');

            $table->index(['company_id', 'is_active'], 'idx_company_certificate_notices_company_active');
            $table->index(['company_id', 'notice_date'], 'idx_company_certificate_notices_company_date');
            $table->index(['company_id', 'source'], 'idx_company_certificate_notices_company_source');
            $table->index(['company_id', 'type'], 'idx_company_certificate_notices_company_type');
            $table->index(['company_id', 'is_read'], 'idx_company_certificate_notices_company_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_certificate_notices');
    }
};
