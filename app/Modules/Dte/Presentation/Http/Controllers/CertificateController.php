<?php

namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\Mappers\CertificateRequestMapper;
use App\Modules\Dte\Application\UseCases\Certificate\ImportCertificateUseCase;
use App\Modules\Dte\Presentation\Http\Requests\ImportCertificateRequest;
use Illuminate\Http\JsonResponse;

class CertificateController extends Controller
{
    public function __construct(
        private readonly ImportCertificateUseCase $importCertificateUseCase,
        private readonly CertificateRequestMapper $certificateRequestMapper,
    ) {
    }

    public function store(ImportCertificateRequest $request): JsonResponse
    {
        $result = $this->importCertificateUseCase->execute(
            $this->certificateRequestMapper->toImportInputDto($request)
        );

        return response()->json([
            'message' => 'Certificado importado correctamente.',
            'data' => [
                'id' => $result->id,
                'company_id' => $result->companyId,
                'alias' => $result->alias,
                'pfx_path' => $result->pfxPath,
                'serial_number' => $result->serialNumber,
                'subject_name' => $result->subjectName,
                'issuer_name' => $result->issuerName,
                'valid_from' => $result->validFrom,
                'valid_to' => $result->validTo,
                'is_default' => $result->isDefault,
                'is_active' => $result->isActive,
                'current_validity_status' => $result->currentValidityStatus,
                'has_private_key' => $result->hasPrivateKey,
            ],
        ], 201);
    }
}
