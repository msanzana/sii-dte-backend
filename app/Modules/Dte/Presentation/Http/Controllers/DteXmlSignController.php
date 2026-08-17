<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\SignDteXmlInputDto;
use App\Modules\Dte\Application\UseCases\Document\SignDteXmlUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\SignDteXmlRequest;
use App\Modules\Dte\Presentation\Http\Resources\SignedDteXmlResource;
use Illuminate\Http\JsonResponse;
use Laravel\SerializableClosure\Serializers\Signed;

class DteXmlSignController extends Controller
{
    public function __construct(
        private readonly SignDteXmlUseCase $signDteXmlUseCase,
    )
    {}

    public function sign(SignDteXmlRequest $request): JsonResponse
    {
        try{
            $result= $this->signDteXmlUseCase->execute(
                new SignDteXmlInputDto(
                    documentId: $request->validated('document_id')
                )
            );

            return response()->json([
                'message' => 'Documento firmado exitosamente',
                'data' => new SignedDteXmlResource($result),
            ],200);
        }
        catch(DomainException $e)
        {
            return response()->json([
                'message' => 'No fue posible firmar el documento',
                'error' => $e->getMessage(),
            ],422);
        }
    }
}
