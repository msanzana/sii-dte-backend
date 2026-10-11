<?php

namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\UseCases\Company\DeleteCompanyLogoUseCase;
use App\Modules\Dte\Application\UseCases\Company\UploadCompanyLogoUseCase;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Presentation\Http\Requests\UploadCompanyLogoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class CompanyLogoController extends Controller
{
    public function __construct(
        private readonly UploadCompanyLogoUseCase $uploadCompanyLogoUseCase,
        private readonly DeleteCompanyLogoUseCase $deleteCompanyLogoUseCase,
    ) {}

    public function store(
        UploadCompanyLogoRequest $request
    ): JsonResponse {
        $companyId =
            (int) $request->attributes->get(
                'auth_company_id'
            );

        $file =
            $request->file('logo');

        if ($file === null) {
            return response()->json([
                'message' =>
                    'Debe adjuntar el logo de la empresa.',
            ], 422);
        }

        $realPath =
            $file->getRealPath();

        if (
            $realPath === false
            || $realPath === ''
        ) {
            throw new RuntimeException(
                'No fue posible acceder al archivo temporal del logo.'
            );
        }

        $contents =
            file_get_contents(
                $realPath
            );

        if ($contents === false) {
            throw new RuntimeException(
                'No fue posible leer el archivo temporal del logo.'
            );
        }

        try {
            $company =
                $this->uploadCompanyLogoUseCase
                    ->execute(
                        companyId:
                            $companyId,
                        contents:
                            $contents,
                        declaredMimeType:
                            (string) $file->getMimeType(),
                    );

            return response()->json([
                'message' =>
                    'Logo de empresa actualizado correctamente.',

                'data' => [
                    'company_id' =>
                        $company->id(),

                    'has_logo' =>
                        $company->hasLogo(),

                    'mime_type' =>
                        $company->logoMimeType(),
                ],
            ]);
        } catch (CompanyNotFoundException $exception) {
            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' =>
                    'El logo enviado no es válido.',

                'error' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function destroy(
        Request $request
    ): JsonResponse {
        $companyId =
            (int) $request->attributes->get(
                'auth_company_id'
            );

        try {
            $company =
                $this->deleteCompanyLogoUseCase
                    ->execute(
                        $companyId
                    );

            return response()->json([
                'message' =>
                    'Logo de empresa eliminado correctamente.',

                'data' => [
                    'company_id' =>
                        $company->id(),

                    'has_logo' =>
                        $company->hasLogo(),
                ],
            ]);
        } catch (CompanyNotFoundException $exception) {
            return response()->json([
                'message' =>
                    $exception->getMessage(),
            ], 404);
        }
    }
}