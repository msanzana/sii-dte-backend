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
        Schema::create('dte_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dte_document_id')
                ->constrained('dte_documents')
                ->cascadeOnDelete();

            $table->unsignedInteger('line_number');
            $table->unsignedSmallInteger('referenced_dte_type')->nullable();
            $table->unsignedBigInteger('referenced_folio')->nullable();
            $table->date('referenced_issue_date')->nullable();

            $table->unsignedTinyInteger('reference_code')->nullable();
            $table->string('reason', 100)->nullable();

            $table->json('extra_payload')->nullable();

            $table->timestamps();

            $table->unique(
                ['dte_document_id', 'line_number'],
                'dte_references_document_line_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_references');
    }
};
