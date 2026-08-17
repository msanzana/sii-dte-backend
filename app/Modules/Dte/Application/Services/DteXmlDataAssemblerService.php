<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\ValueObjects\LocationSummary;

final class DteXmlDataAssemblerService
{
    public function assemble(
        DteDocument $document,
        Company $company,
        LocationSummary $emitterLocation,
        ?LocationSummary $receiverLocation
    ): array
    {
        $documentXmlId = sprintf(
            'DTE_F%s_T%s',
            $document->folio(),
            $document->dteType()->value
        );

        $details = [];

        foreach($document->items() as $item)
        {
            $details[] = [
                'line_number' => $item->lineNumber(),
                'item_code_type' => $item->itemCodeType(),
                'item_code' => $item->itemCode(),
                'tax_exempt' => $item->taxExempt(),
                'name' => $item->name(),
                'description' => $item->description(),
                'quantity' => $this->formatDecimal($item->quantity()),
                'unit_price' => $this->formatDecimal($item->unitPrice()),
                'line_amount' => $this->formatDecimal($item->lineAmount()),
            ];
        }

        $references = [];
        foreach($document->references() as $reference)
        {
            $references[] = [
                'line_number' => $reference->lineNumber(),
                'referenced_dte_type' => $reference->referencedDteType(),
                'referenced_folio' => $reference->referencedFolio() !== null
                    ? (string) $reference->referencedFolio()
                    : null,
                'referenced_issue_date' => $reference->referencedIssueDate(),
                'reference_code' => $reference->referenceCode(),
                'reason' => $reference->reason(),
            ];
        }

        return [
            'version' => '1.0',
            'document_xml_id' => $documentXmlId,

            'id_doc' => [
                'tipo_dte' => (string) $document->dteType()->value,
                'folio' => (string) $document->folio(),
                'fecha_emision' => $document->issueDate(),
            ],

            'emitter' => [
                'rut' => $company->rut(),
                'razon_social' => $company->legalName(),
                'giro' => $company->giro(),
                'email' => $company->dteEmail(),
                'acteco' => $company->siiActivityCode(),
                'direccion' => $company->address(),
                'commune' => $emitterLocation->comuneName(),
                'city' => $emitterLocation-> cityName(),
            ],

            'receiver' => [
                'rut' => $document->receiver()->document(),
                'razon_social' => $document->receiver()->name(),
                'giro' => $document->receiver()->giro(),
                'direccion' => $document->receiver()->address(),
                'commune' => $receiverLocation?->comuneName(),
                'city' => $receiverLocation?->cityName(),
                'email' => $document->receiver()->email(),
            ],

            'totals' => [
                'net_amount' => $document->netAmount() > 0
                    ? $this->formatIntegerAmount($document->netAmount())
                    : null,
                'exempt_amount' => $document->exemptAmount() > 0
                    ? $this->formatIntegerAmount($document->exemptAmount())
                    : null,
                'tax_rate' => $document->taxAmount() > 0 ? '19' : null,
                'tax_amount' => $document->taxAmount() > 0
                    ? $this->formatIntegerAmount($document->taxAmount())
                    : null,
                'total_amount' => $this->formatIntegerAmount($document->totalAmount()),
            ],

            'details' => $details,
            'references' => $references,
        ];
    }
    private function formatIntegerAmount(float $value): string
    {
        return (string) round($value, 0);
    }
    public function formatDecimal(float $value): string
    {
        $formatted = number_format($value,6,'.','');
        $formatted = rtrim($formatted,'0');
        $formatted = rtrim($formatted,'.');

        return $formatted === '' ? '0' : $formatted;
    }

}
