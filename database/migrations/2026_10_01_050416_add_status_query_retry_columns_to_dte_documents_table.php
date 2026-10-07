<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table
                    ->unsignedTinyInteger(
                        'status_query_retry_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'status_query_next_at'
                    )
                    ->nullable();

                $table->index(
                    [
                        'status',
                        'status_query_next_at',
                    ],
                    'dte_documents_status_query_retry_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'dte_documents_status_query_retry_idx'
                );

                $table->dropColumn([
                    'status_query_retry_count',
                    'status_query_next_at',
                ]);
            }
        );
    }
};