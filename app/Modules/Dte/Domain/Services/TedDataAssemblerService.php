<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;
use App\Modules\Dte\Domain\Services\TedEncodingService;

final class TedDataAssemblerService
{
    public function assemble(
        DteDocument $document,
        Company $company,
        string $cafXmlFragment,
    ):array
    {
        $firstItem = $document->items()[0] ?? null;

        if($firstItem === null) {
            throw InvalidTedDataException::because(
                "El documento {$document->id()} no tiene primer item para construir IT1."
            );
        }

        return [
            'version' => '1.0',
            're' => $company->rut(),
            'td' => (string) $document->dteType()->value,
            'f' => (string) $document->folio(),
            'fe' => $document->issueDate(),
            'rr' => $document->receiver()->document(),
            'rsr' => $this->normalizeTedText($document->receiver()->name()),
            'mnt' => $this->formatIntegerAmount($document->totalAmount()),
            'it1' => $this->normalizeTedText($firstItem->name()),
            'caf_xml_fragment' => $cafXmlFragment,
            'tsted' => now('America/Santiago')->format('Y-m-d\TH:i:s'),
        ];
    }
    private function formatIntegerAmount(float $value): string
    {
        return (string) round($value,0);
    }

    public function normalizeTedText(
        string $value
    ): string {
        return TedEncodingService::normalizeUtf8Text(
            $value
        );
    }
}
