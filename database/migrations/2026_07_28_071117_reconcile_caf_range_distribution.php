<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropIndexIfExists(
            table: "sii_cafs",
            indexName: "idx_sii_cafs_company_system_doc"
        );
        $this->dropIndexIfExists(
            table:"sii_cafs",
            indexName: "idx_sii_cafs_distribution"
        );
        Schema::table('sii_cafs', function (Blueprint $table) {
            if(Schema::hasColumn('sii_cafs','sii_document_type_code'))
            {
                $table->dropColumn('sii_document_type_code');
            }
            if(Schema::hasColumn('sii_cafs','branch_office_number'))
            {
                $table->dropColumn('branch_office_number');
            }
            if(Schema::hasColumn('sii_cafs','facility_number'))
            {
                $table->dropColumn('facility_number');
            }
        });

        if(
            Schema::hasTable('folio_details')
            && $this->indexExists(
                table: 'folio_details',
                indexName: 'uq_folio_details_caf_folio'
            )
        )
        {
            Schema::table('folio_details', function (Blueprint $table){
                $table->unique(
                    ['caf_id','folio_number'],
                    'uq_folio_details_caf_folio'
                );
            });
        }

        if(
            Schema::hasTable('folio_reservations')
            && $this->indexExists(
                table: 'folio_reservations',
                indexName: 'idx_folio_reservations_caf_range'
            )
        )
        {
            Schema::table('folio_reservations',function(Blueprint $table){
                $table->intex(
                    [
                        'caf_id',
                        'folio_range_from',
                        'folio_range_to',
                    ],'idx_folio_reservations_caf_range'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists(
            table: 'folio_details',
            indexName: 'uq_folio_details_caf_folio'
        );

        $this->dropIndexIfExists(
            table: 'folio_reservations',
            indexName: 'idx_folio_reservations_caf_range'
        );

        Schema::table('sii_cafs', function (Blueprint $table){
            if(!Schema::hasColumn('sii_cafs','sii_document:type_code')){
                $table->string('sii_document_type_code',20)
                    ->nulñlable()
                    ->after('external_system_id');
            }
            if(!Schema::hasColumn('sii_cafs','branch_office_number'))
            {
                $table->unsignedInteger('branch_office_number')
                    ->nullable()
                    ->after('sii_document_type_code');
            }
            if(!Schema::hasColumn('sii_cafs', 'facility_number'))
            {
                $table->unsignedInteger('facility_number')
                    ->nullable()
                    ->after('branch_office_number');
            }
        });
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

    private function dropIndexIfExists(
        string $table,
        string $indexName
    ): void {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            str_replace('`', '', $table),
            str_replace('`', '', $indexName)
        ));
    }
};
