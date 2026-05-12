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
        Schema::create('dte_line_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dte_document_id')
                ->constrained('dte_documents')
                ->cascadeOnDelete();

            $table->unsignedInteger('line_number');

            $table->string('item_code_type', 20)->nullable();
            $table->string('item_code', 50)->nullable();

            $table->string('name', 120);
            $table->text('description')->nullable();

            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);

            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->boolean('tax_exempt')->default(false);
            $table->decimal('line_amount', 18, 2)->default(0);

            $table->json('extra_payload')->nullable();

            $table->timestamps();

            $table->unique(
                ['dte_document_id', 'line_number'],
                'dte_line_items_document_line_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_line_items');
    }
};
