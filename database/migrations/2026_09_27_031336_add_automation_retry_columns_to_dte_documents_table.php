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
                    ->string(
                        'automation_retry_action',
                        40
                    )
                    ->nullable();

                $table
                    ->unsignedTinyInteger(
                        'automation_retry_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'automation_next_retry_at'
                    )
                    ->nullable();

                $table->index(
                    [
                        'status',
                        'automation_next_retry_at',
                    ],
                    'dte_documents_automation_retry_idx'
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
                    'dte_documents_automation_retry_idx'
                );

                $table->dropColumn([
                    'automation_retry_action',
                    'automation_retry_count',
                    'automation_next_retry_at',
                ]);
            }
        );
    }
};