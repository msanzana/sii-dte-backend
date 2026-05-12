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
        Schema::create('sii_dispatches', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_uuid')->unique();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->foreignId('dte_document_id')
                ->nullable()
                ->constrained('dte_documents')
                ->nullOnDelete();

            $table->string('environment', 10)->default('cert');
            $table->string('transport_type', 20);
            $table->string('status', 40)->default('pending');

            $table->string('track_id', 30)->nullable();
            $table->string('request_identifier', 100)->nullable();

            $table->string('request_path', 255)->nullable();
            $table->json('request_headers')->nullable();
            $table->string('request_body_path', 255)->nullable();

            $table->unsignedInteger('response_http_status')->nullable();
            $table->longText('response_body')->nullable();

            $table->string('upload_status_code', 20)->nullable();
            $table->string('upload_status_message', 255)->nullable();

            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['track_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sii_dispatches');
    }
};
