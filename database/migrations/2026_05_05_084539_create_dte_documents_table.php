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
        Schema::create('dte_documents', function (Blueprint $table) {
            $table->id();

            $table->uuid('external_id')->unique();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->unsignedSmallInteger('dte_type');
            $table->unsignedBigInteger('folio')->nullable();

            $table->date('issue_date');
            $table->string('status', 40)->default('draft');
            $table->string('sii_environment', 10)->default('cert');

            $table->string('receiver_document', 20);
            $table->string('receiver_name', 120);
            $table->string('receiver_giro', 150)->nullable();
            $table->string('receiver_address', 150)->nullable();

            $table->foreignId('receiver_city_id')->nullable()->constrained('cities')->restrictOnDelete();

            $table->string('receiver_email', 150)->nullable();

            $table->decimal('net_amount', 18, 2)->default(0);
            $table->decimal('exempt_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->json('header_payload')->nullable();
            $table->json('totals_payload')->nullable();
            $table->json('raw_input')->nullable();
            $table->json('validation_warnings')->nullable();

            $table->string('unsigned_xml_path', 255)->nullable();
            $table->string('signed_xml_path', 255)->nullable();
            $table->longText('ted_xml')->nullable();

            $table->string('last_error_code', 50)->nullable();
            $table->text('last_error_message')->nullable();

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'dte_type', 'folio']);
            $table->index(['company_id', 'issue_date']);
            $table->index(['receiver_city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_documents');
    }
};
