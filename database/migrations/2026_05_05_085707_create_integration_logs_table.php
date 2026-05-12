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
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->foreignId('dte_document_id')
                ->nullable()
                ->constrained('dte_documents')
                ->nullOnDelete();

            $table->string('channel', 40);
            $table->string('level', 20);
            $table->string('code', 50)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index(['channel', 'level']);
            $table->index(['occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
