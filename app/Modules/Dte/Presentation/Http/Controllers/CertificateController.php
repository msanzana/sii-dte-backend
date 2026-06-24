<?php

namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\ImportCertificateInputDto;
use App\Modules\Dte\Application\UseCases\Certificate\ImportCertificateUseCase;
use App\Modules\Dte\Presentation\Http\Requests\ImportCertificateRequest;
use Illuminate\Http\JsonResponse;

class CertificateController extends Controller
{
    public function __construct(
        private readonly ImportCertificateUseCase $importCertificateUseCase,
    ) {
    }

    public function store(ImportCertificateRequest $request): JsonResponse
    {
        try {
            $certificateFile = $request->file('pfx_file');

            if (!$certificateFile|| !$certificateFile->isValid()) {
                return response()->json([
                    'message' => 'No fue posible importar el certificado.',
                    'error' => 'Debes adjuntar un archivo de certificado válido.',
                ], 422);
            }

            $result = $this->importCertificateUseCase->execute(
                new ImportCertificateInputDto(
                    companyId: (int) $request->validated('company_id'),
                    alias: (string) $request->validated('alias'),
                    originalFilename: (string) $certificateFile->getClientOriginalName(),
                    tempFilepath: (string) $certificateFile->getRealPath(),
                    pfxPassword: (string) $request->validated('pfx_password'),
                    isActive: (bool) ($request->validated('is_active') ?? true),
                )
            );

            return response()->json([
                'message' => 'Certificado importado correctamente.',
                'data' => [
                    //'certificate_id' => $result->certificateId,
                    'company_id' => $result->companyId,
                    'alias' => $result->alias,
                    'subject_name' => $result->subjectName,
                    'issuer_name' => $result->issuerName,
                    'serial_number' => $result->serialNumber,
                    'valid_from' => $result->validFrom,
                    'valid_to' => $result->validTo,
                    'is_active' => $result->isActive,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'No fue posible importar el certificado.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
