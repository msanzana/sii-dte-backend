<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;

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
            'tsted' => now()->format('Y-m-d\TH:i:s'),
        ];
    }
    private function formatIntegerAmount(float $value): string
    {
        return (string) round($value,0);
    }

    public function normalizeTedText(string $value): string
    {
        $collapsed = preg_replace('/\s+/u',' ',trim($value));

        if($collapsed === null || $collapsed === '') {
            throw InvalidTedDataException::because(
                'Uno de los textos del TED quedó vacío después de normalizar espacios.'
            );
        }

        $latin1 = iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE', $collapsed);

        if($latin1 === false || $latin1 === "") {
            throw InvalidTedDataException::because(
                'No fue posible representar un texto del TED en ISO-8859-1.'
            );
        }

        $utf8 = iconv('ISO-8859-1', 'UTF-8', $latin1);

        if($utf8 === false || $utf8 === '') {
            throw InvalidTedDataException::because(
                'No fue posible normalizar de vuelta a UTF8 un texto del TED.'
            );
        }

        return $utf8;
    }
}
