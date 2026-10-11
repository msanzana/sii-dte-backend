<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('dte_document_type_print_copies')
            ->where('code', 'cedible')
            ->whereIn(
                'dte_document_type_id',
                function ($query): void {
                    $query
                        ->select('id')
                        ->from('dte_document_types')
                        ->whereIn(
                            'code',
                            [33, 34, 46, 52]
                        );
                }
            )
            ->update([
                'is_required' => true,
                'parameters' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $standardCedibleParameters = json_encode(
            [
                'conditional' => true,
                'rule' => 'cedible_when_legally_applicable',
            ],
            JSON_THROW_ON_ERROR
        );

        $guideCedibleParameters = json_encode(
            [
                'conditional' => true,
                'rule' => 'cedible_with_invoice_when_applicable',
            ],
            JSON_THROW_ON_ERROR
        );

        DB::table('dte_document_type_print_copies')
            ->where('code', 'cedible')
            ->whereIn(
                'dte_document_type_id',
                function ($query): void {
                    $query
                        ->select('id')
                        ->from('dte_document_types')
                        ->whereIn(
                            'code',
                            [33, 34, 46]
                        );
                }
            )
            ->update([
                'is_required' => false,
                'parameters' => $standardCedibleParameters,
                'updated_at' => now(),
            ]);

        DB::table('dte_document_type_print_copies')
            ->where('code', 'cedible')
            ->whereIn(
                'dte_document_type_id',
                function ($query): void {
                    $query
                        ->select('id')
                        ->from('dte_document_types')
                        ->where('code', 52);
                }
            )
            ->update([
                'is_required' => false,
                'parameters' => $guideCedibleParameters,
                'updated_at' => now(),
            ]);
    }
};