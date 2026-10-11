<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'dte_document_type_print_copies',
            function (Blueprint $table): void {
                $table->unsignedSmallInteger('default_quantity')
                    ->default(1)
                    ->after('is_required');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'dte_document_type_print_copies',
            function (Blueprint $table): void {
                $table->dropColumn('default_quantity');
            }
        );
    }
};