<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('folio_details')) {
            return;
        }

        $hasDuplicates = DB::table('folio_details')
            ->select([
                'caf_id',
                'folio_number',
                DB::raw('COUNT(*) AS duplicate_count'),
            ])
            ->groupBy('caf_id', 'folio_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Existen folios duplicados en folio_details. Debes limpiarlos antes de crear la restricción única.'
            );
        }

        if (!$this->indexExists(
            'folio_details',
            'uq_folio_details_caf_folio'
        )) {
            Schema::table('folio_details', function (Blueprint $table) {
                $table->unique(
                    ['caf_id', 'folio_number'],
                    'uq_folio_details_caf_folio'
                );
            });
        }

        if (
            Schema::hasTable('folio_reservations')
            && !$this->indexExists(
                'folio_reservations',
                'idx_folio_reservations_caf_range'
            )
        ) {
            Schema::table(
                'folio_reservations',
                function (Blueprint $table) {
                    $table->index(
                        [
                            'caf_id',
                            'folio_range_from',
                            'folio_range_to',
                        ],
                        'idx_folio_reservations_caf_range'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        if ($this->indexExists(
            'folio_details',
            'uq_folio_details_caf_folio'
        )) {
            Schema::table('folio_details', function (Blueprint $table) {
                $table->dropUnique(
                    'uq_folio_details_caf_folio'
                );
            });
        }

        if ($this->indexExists(
            'folio_reservations',
            'idx_folio_reservations_caf_range'
        )) {
            Schema::table(
                'folio_reservations',
                function (Blueprint $table) {
                    $table->dropIndex(
                        'idx_folio_reservations_caf_range'
                    );
                }
            );
        }
    }

    private function indexExists(
        string $table,
        string $indexName
    ): bool {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
