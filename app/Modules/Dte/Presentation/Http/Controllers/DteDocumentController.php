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
        $data = $request->validated();

        $companyId = (int) $request->attributes->get('auth_company_id');

        $items = [];
        foreach($data['items'] as $item)
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
                referenceCode: $reference['reference_code'] ?? null,
                reason: $reference['reason'] ?? null,
                extraPayload: $reference['extra_payload'] ?? null,
            );
        }
        $input = new CreateDteDocumentInputDto(
            companyId: $companyId,
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
            externalSystemId: isset($data['external_system_id']) ? (int) $data['external_system_id'] : null,
            proposedFolio: isset($data['proposed_folio'])? (int) $data['proposed_folio'] : null,
            proposedSiiDocumentType:isset($data['proposed_sii_document_type'])? (int) $data['proposed_sii_document_type'] : null,
            branchOfficeNumber:isset($data['branch_office_number'])? (int) $data['branch_office_number'] : null,
            facilityNumber: isset($data['facility_number'])? (int) $data['facility_number'] : null,
            externalBranchCode: isset($data['external_branch_code']) && trim((string) $data['external_branch_code']) !== '' ? trim((string) $data['external_branch_code']) : null,
        );

        try{
            $result = $this->createDteDocumentUseCase->execute($input);
            return response()->json([
                'message' => 'Documento registrado.',
                'data' => new DteDocumentResource($result),
            ],201);

        }
        catch (\RuntimeException $exception) {
            return response()->json([
                'message' =>
                    'No fue posible registrar el documento.',

                'error' =>
                    $exception->getMessage(),
            ], 422);
        }
    }
}
