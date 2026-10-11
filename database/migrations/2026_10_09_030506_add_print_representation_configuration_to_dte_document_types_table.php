<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dte_document_types', function (Blueprint $table): void {
            $table->boolean('is_printable')
                ->default(true)
                ->after('is_active');

            $table->string('default_print_format', 30)
                ->default('letter')
                ->after('sort_order');

            $table->json('print_parameters')
                ->nullable()
                ->after('parameters');

            $table->index(
                ['is_printable', 'is_active'],
                'idx_dte_document_types_printable_active'
            );
        });

        Schema::create('dte_document_type_print_copies', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('dte_document_type_id');

            $table->string('code', 40);
            $table->string('name', 120);

            $table->boolean('is_required')
                ->default(true);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->string('legend', 180)
                ->nullable();

            $table->json('parameters')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['dte_document_type_id', 'code'],
                'uq_dte_print_copy_type_code'
            );

            $table->index(
                ['dte_document_type_id', 'is_active', 'sort_order'],
                'idx_dte_print_copy_type_active'
            );

            $table->foreign('dte_document_type_id', 'fk_dte_print_copy_document_type')
                ->references('id')
                ->on('dte_document_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_document_type_print_copies');

        Schema::table('dte_document_types', function (Blueprint $table): void {
            $table->dropIndex('idx_dte_document_types_printable_active');

            $table->dropColumn([
                'is_printable',
                'default_print_format',
                'print_parameters',
            ]);
        });
    }
};
