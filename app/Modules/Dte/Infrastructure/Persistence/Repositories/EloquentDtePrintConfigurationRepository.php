<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\DtePrintConfiguration;
use App\Modules\Dte\Domain\Entities\DtePrintCopyConfiguration;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\RepositoryContracts\DtePrintConfigurationRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentDtePrintConfigurationRepository
    implements DtePrintConfigurationRepositoryInterface
{
    public function findByDteTypeCode(
        int $dteType
    ): ?DtePrintConfiguration {
        $type = DB::table('dte_document_types')
            ->where('code', $dteType)
            ->first();

        if ($type === null) {
            return null;
        }

        $copies = DB::table('dte_document_type_print_copies')
            ->where(
                'dte_document_type_id',
                (int) $type->id
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(
                fn (object $row): DtePrintCopyConfiguration =>
                    new DtePrintCopyConfiguration(
                        id: (int) $row->id,
                        type: PrintCopyType::from(
                            (string) $row->code
                        ),
                        name: (string) $row->name,
                        isRequired: (bool) $row->is_required,
                        defaultQuantity: (int) $row->default_quantity,
                        isActive: (bool) $row->is_active,
                        sortOrder: (int) $row->sort_order,
                        legend: $row->legend !== null
                            ? (string) $row->legend
                            : null,
                        parameters: $this->decodeJson(
                            $row->parameters !== null
                                ? (string) $row->parameters
                                : null
                        ),
                    )
            )
            ->all();

        return new DtePrintConfiguration(
            dteDocumentTypeId: (int) $type->id,
            dteType: (int) $type->code,
            name: (string) $type->name,
            isActive: (bool) $type->is_active,
            isPrintable: (bool) $type->is_printable,
            defaultFormat: PrintFormat::from(
                (string) $type->default_print_format
            ),
            printParameters: $this->decodeJson(
                $type->print_parameters !== null
                    ? (string) $type->print_parameters
                    : null
            ),
            copies: $copies,
        );
    }

    private function decodeJson(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode(
            $value,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return is_array($decoded)
            ? $decoded
            : null;
    }
}