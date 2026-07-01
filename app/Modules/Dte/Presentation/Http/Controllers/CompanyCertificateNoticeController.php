<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\CreateManualCertificateNoticeInputDto;
use App\Modules\Dte\Application\DTOs\UpdateManualCertificateNoticeInputDto;
use App\Modules\Dte\Application\UseCases\CertificateNotice\CreateManualCertificateNoticeUseCase;
use App\Modules\Dte\Application\UseCases\CertificateNotice\DeleteCertificateNoticeUseCase;
use App\Modules\Dte\Application\UseCases\CertificateNotice\ListCompanyCertificateNoticesUseCase;
use App\Modules\Dte\Application\UseCases\CertificateNotice\MarkCertificateNoticeAsReadUseCase;
use App\Modules\Dte\Application\UseCases\CertificateNotice\UpdateManualCertificateNoticeUseCase;
use App\Modules\Dte\Presentation\Http\Requests\StoreManualCertificateNoticeRequest;
use App\Modules\Dte\Presentation\Http\Requests\UpdateManualCertificateNoticeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class CompanyCertificateNoticeController extends Controller
{
    public function __construct(
        private readonly ListCompanyCertificateNoticesUseCase $listCompanyCertificateNoticeUseCase,
        private readonly CreateManualCertificateNoticeUseCase $createManualCertificateNoticeUseCase,
        private readonly UpdateManualCertificateNoticeUseCase $updateManualCertificateNoticeUseCase,
        private readonly MarkCertificateNoticeAsReadUseCase $markCertificateNoticeAsReadUseCase,
        private readonly DeleteCertificateNoticeUseCase $deleteCertificateNoticeUseCase
    )
    {}

    public function index(Request $request): JsonResponse
    {
        $companyId =(int) $request->attributes->get('auth_company_id');
        $result = $this->listCompanyCertificateNoticeUseCase->execute($companyId);

        return response()->json([
            'message' => 'Avisos obtenidos correctamente.',
            'data' => array_map(fn ($item) => [
                'id' => $item->id,
                'company_id' => $item->companyId,
                'user_id' => $item->userId,
                'certificate_id' => $item->certificateId,
                'source' => $item->source,
                'type' => $item->typet,
                'code' => $item->code,
                'title'=> $item->title,
                'message' => $item->message,
                'notice_date' => $item->noticeDate,
                'notice_time' => $item->noticeTime,
                'emitted_at' => $item->emittedAt,
                'is_read' => $item->isRead,
                'is_active' => $item->isActive,

            ], $result->items),
        ]);
    }

    public function storeManual(StoreManualCertificateNoticeRequest $request): JsonResponse
    {
        $this->createManualCertificateNoticeUseCase->execute(
            new CreateManualCertificateNoticeInputDto(
                companyId:(int) $request->attributes->get('auth_company_id'),
                userId: (int) $request->attributes->get('auth_user_id'),
                type: (string) $request->validated('type'),
                title: (string) $request->validated('title'),
                message: (string) $request->validated('message')
            )
        );
        return response()->json([
            'message' => 'Aviso manual registrado correctamente.',
        ],200);
    }

    public function updateManual(int $noticeId, UpdateManualCertificateNoticeRequest $request): JsonResponse
    {
        $this->updateManualCertificateNoticeUseCase->execute(
            new UpdateManualCertificateNoticeInputDto(
                noticeId: $noticeId,
                type: (string) $request->validated('type'),
                title: (string) $request->validated('title'),
                message: (string) $request->validated('message'),
                isActive: (bool) $request->validated('is_active')
            )
        );

        return response()->json([
            'message' => 'Aviso manual actualizado correctamente',
        ]);
    }

    public function markRead(int $noticeId): JsonResponse
    {
        $this->markCertificateNoticeAsReadUseCase->execute($noticeId);

        return response()->json([
            'message' => 'Aviso marcado como leido correctamente.',
        ]);
    }

    public function destroy(int $noticeId): JsonResponse
    {
        $this->deleteCertificateNoticeUseCase->execute($noticeId);

        return response()->json([
            'message' => 'Aviso eliminado correctamente.',
        ]);
    }
}


