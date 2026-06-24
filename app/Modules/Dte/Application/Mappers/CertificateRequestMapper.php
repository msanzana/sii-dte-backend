<?php
namespace App\Modules\Dte\Application\Mappers;

use App\Modules\Dte\Application\DTOs\ImportCertificateInputDto;
use App\Modules\Dte\Presentation\Http\Requests\ImportCertificateRequest;

final Class CertificateRequestMapper
{
    public function toImportInputDto(ImportCertificateRequest $request):ImportCertificateInputDto
    {
        $file = $request->file('pfx_file');

        return new ImportCertificateInputDto(
            companyId: (int) $request->validated('company_id'),
            alias: (string) $request->validated('alias'),
            originalFilename: (string) $file->getClientOriginalName(),
            tempFilepath: (string) $file->getRealPath(),
            pfxPassword: (string) $request->validated('pfx_password'),
            isActive: (bool) ($request->validated('is_active') ?? true)
        );
    }
}
