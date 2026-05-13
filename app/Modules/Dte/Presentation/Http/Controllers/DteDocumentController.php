<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\DteLineItemInputDto;
use App\Modules\Dte\Application\DTOs\DteReferenceInputDto;
use App\Modules\Dte\Application\DTOs\ReceiverInputDto;
use App\Modules\Dte\Application\UseCases\Document\CreateDteDocumentUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\StoreDteDocumentRequest;
use App\Modules\Dte\Presentation\Http\Resources\DteDocumentResource;
use Illuminate\Http\JsonResponse;

class DteDocumentController extends Controller
{
    public function __construct(
        private readonly CreateDteDocumentUseCase $createDteDocumentUseCase,
    )
    {}

    public function store(StoreDteDocumentRequest $request): JsonResponse{
        $data = $request->validated;

        $items = [];
        foreach($data['item'] as $item)
        {
            $items[] = new DteLineItemInputDto(
                itemCodeType: $item['item_code_type'] ?? null,
                itemCode: $item['item_code'] ?? null,
                name: $item['name'],
                description: $item['description'] ?? null,
                quantity: (float) $item['quantity'],
                unitPrice: (float) $item['unit_price'],
                discountPercent: (float) $item['discount_percent'],
                discountAmount: (float) $item['discount_amount'],
                taxExempt: (bool) ($item['tax_exempt'] ?? false),
                extraPayload: $item['extra_payload'] ?? null,
            );
        }
        $references = [];
        foreach(($data['references'] ?? []) as $reference)
        {
            $references[] = new DteReferenceInputDto(
                referencedDteType: $reference['referenced_dte_type'] ?? null,
                referencedFolio: $reference['referenced_folio'] ?? null,
                referencedIssueDate: $reference['referenced_issue_date'] ?? null,
                referencedCode: $reference['referenced_code'] ?? null,
                reason: $reference['reason'] ?? null,
                extraPayload: $reference['extra_payload'] ?? null,
            );
        }
        $input = new CreateDteDocumentInputDto(
            companyId: (int) $data['company_id'],
            dteType: (int) $data['dte_type'],
            issueDate: $data['issue_date'],
            receiver: new ReceiverInputDto(
                document: $data['receiver']['document'],
                name: $data['receiver']['name'],
                giro: $data['receiver']['giro'] ?? null,
                address: $data['address'] ?? null,
                cityId: isset($data['receiver']['city_id']) ? (int) $data['receiver']['city_id'] : null,
                email: $data['receiver']['email'] ?? null,
            ),
            items: $items,
            references: $references,
            externalId: $data['external_id'] ?? null,
            headerPayload: $data['header_payload'] ?? null,
            rawInput: $data,
        );

        try{
            $result = $this->createDteDocumentUseCase->execute($input);
            return response()->json([
                'message' => 'Documento registrado.',
                'data' => new DteDocumentResource((Object) [
                    'id' => $result->id,
                    'externalId' => $result->externalId,
                    'status' => $result->status,
                    'dteType' => $result->dteType,
                    'issueDate' => $result->issueDate,
                    'receiverCityId' => $result->receiverCityId,
                    'netAmount' => $result->netAmount,
                    'exemptAmount' => $result->exemptAmount,
                    'taxAmount' => $result->taxAmount,
                    'totalAmount' => $result->totalAmount,
                ]),
            ],201);
        }
        catch(DomainException $e)
        {
            return response()->json([
                'message' => 'No fue posible registrar el documento.',
                'error' => $e->getMessage(),
            ],422);
        }
    }
}
