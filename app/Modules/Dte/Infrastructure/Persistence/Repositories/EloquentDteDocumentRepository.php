<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteLineItemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteReferenceEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;


final class EloquentDteDocumentRepository implements DteDocumentRepositoryInterface
{
    public function __construct(
        private readonly DteDocumentPersistenceMapper $mapper
    ) {}



    public function create(DteDocument $document): DteDocument
    {
        $model = new DteDocumentEloquentModel();
        $model->fill([
            'external_id' => $document->externalId(),
            'company_id' => $document->companyId(),
            'dte_type' => $document->dteType(),
            'folio' => $document->folio(),
            'issue_date' => $document->issueDate(),
            'status' => $document->status(),
            'sii_environment' => $document->siiEnvironment(),
            'receiver_document' => $document->receiver()->document(),
            'receiver_name' => $document->receiver()->name(),
            'receiver_giro' => $document->receiver()->giro(),
            'receiver_address' => $document->receiver()->address(),
            'receiver_city_id' => $document->receiver()->cityId(),
            'receiver_email' => $document->receiver()->email(),
            'net_amount' => $document->netAmount(),
            'exempt_amount' => $document->netAmount(),
            'tax_amount' => $document->taxAmount(),
            'total_amount' => $document->totalAmount(),
            'header_payload' => $document->headerPayload(),
            'totals_payload' => $document->totalsPayload(),
            'raw_input' => $document->rawInput(),
            'unsigned_xml_path' => $document->unsignedXmlPath(),
            'signed_xml_path' => $document->signedXmlPath(),
            'ted_xml' => $document->tedXml(),
            'last_error_code' => $document->lastErrorCode(),
            'last_error_message' => $document->lastErrorMessage(),

        ]);

        $model->save();

        foreach($document->items() as $item)
        {
            DteLineItemEloquentModel::query()->create([
                'dte_document_id' => $model->id,
                'line_number' => $item->lineNumber(),
                'item_code_type' => $item->itemCodeType(),
                'item_code' => $item->itemCode(),
                'name'  => $item->name(),
                'description' => $item->description(),
                'quantity' => $item->quantity(),
                'unit_price' => $item->unitPrice(),
                'discount_percent' => $item->discountPercent(),
                'discount_amount' => $item->discountAmount(),
                'tax_exempt' => $item->taxExempt(),
                'lineAmount' => $item->lineAmount(),
                'extra_payload' => $item->extraPayload(),
            ]);
        }
        foreach($document->references() as $reference)
        {
            DteReferenceEloquentModel::query()->create([
                'dte_document_id' => $model->id,
                'line_number' => $reference->lineNumber(),
                'referenced_dte_type' => $reference->referencedDteType(),
                'referencedFolio' => $reference->referencedFolio(),
                'referenced_issue_date' => $reference->referencedIssueDate(),
                'reference_code' => $reference->referenceCode(),
                'reason' => $reference->reason(),
                'extra_payload' => $reference->extraPayload(),
            ]);
        }
        return $this->findById((int) $model->id);
    }

    public function update(DteDocument $document): DteDocument
    {
        $model = DteDocumentEloquentModel::query()->findOrFail($document->id());
        $model->fill([
            'folio' => $document->folio(),
            'issue_date' => $document->issueDate(),
            'status' => $document->status(),
            'sii_environment' => $document->siiEnvironment(),
            'receiver_document' => $document->receiver()->document(),
            'receiver_name' => $document->receiver()->name(),
            'receiver_giro' => $document->receiver()->giro(),
            'receiver_address' => $document->receiver()->address(),
            'receiver_city_id' => $document->receiver()->cityId(),
            'receiver_email' => $document->receiver()->email(),
            'net_amount' => $document->netAmount(),
            'exempt_amount' => $document->netAmount(),
            'tax_amount' => $document->taxAmount(),
            'total_amount' => $document->totalAmount(),
            'header_payload' => $document->headerPayload(),
            'totals_payload' => $document->totalsPayload(),
            'raw_input' => $document->rawInput(),
            'unsigned_xml_path' => $document->unsignedXmlPath(),
            'signed_xml_path' => $document->signedXmlPath(),
            'ted_xml' => $document->tedXml(),
            'last_error_code' => $document->lastErrorCode(),
            'last_error_message' => $document->lastErrorMessage(),
        ]);

        $model->save();
        return $this->findById((int) $model->id());
    }

    public function findById(int $id): ?DteDocument
    {
        $model = DteDocumentEloquentModel::query()
                ->with(['items','references'])
                ->find($id);
        if(!$model)
        {
            return null;
        }
        return $this->mapper->toDomain($model);
    }

    public function findByExternalId(string $externalId): ?DteDocument
    {
        $model = DteDocumentEloquentModel::query()
            ->with(['items', 'references'])
            ->where('external_id', $externalId)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findPendingForDispatch(int $limit = 100): array
    {

        return DteDocumentEloquentModel::query()
                 ->with(['items','references'])
                 ->whereIn('status',['signed','queued'])
                 ->orderBy('id')
                 ->limit($limit)
                 ->get()
                 ->map(fn(DteDocumentEloquentModel $model) => $this->mapper->toDomain($model))
                 ->all();
    }
}
