<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Illuminate\Support\Carbon;

final class EloquentDteDocumentAutomationEligibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('sii_dispatches');
        Schema::dropIfExists('dte_documents');

        Schema::create(
            'dte_documents',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'status',
                    40
                );

                // 🟩 NUEVO
                $table
                    ->string(
                        'automation_retry_action',
                        40
                    )
                    ->nullable();

                // 🟩 NUEVO
                $table
                    ->unsignedTinyInteger(
                        'automation_retry_count'
                    )
                    ->default(0);

                // 🟩 NUEVO
                $table
                    ->timestamp(
                        'automation_next_retry_at'
                    )
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create('sii_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dte_document_id');
            $table->string('status', 40);
            $table->string('upload_status_code', 20)->nullable();

            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('sii_dispatches');
        Schema::dropIfExists('dte_documents');

        parent::tearDown();
    }

    public function test_excluye_signed_cuyo_ultimo_dispatch_es_upload_rejected(): void
    {
        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            $documentId,
            DispatchStatus::UPLOAD_REJECTED->value
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }

    public function test_incluye_signed_sin_dispatch(): void
    {
        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertContains(
            $documentId,
            $ids
        );
    }

    public function test_un_upload_rejected_historico_no_bloquea_si_existe_un_dispatch_posterior(): void
    {
        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            $documentId,
            DispatchStatus::UPLOAD_REJECTED->value
        );

        $this->crearDispatch(
            $documentId,
            DispatchStatus::REJECTED->value,
            'RSC'
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertContains(
            $documentId,
            $ids
        );
    }

    public function test_el_limite_se_aplica_despues_de_excluir_documentos_bloqueados(): void
    {
        $blockedDocumentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            $blockedDocumentId,
            DispatchStatus::UPLOAD_REJECTED->value
        );

        $eligibleDocumentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                1
            );

        $this->assertSame(
            [$eligibleDocumentId],
            $ids
        );
    }

    private function repository(): EloquentDteDocumentRepository
    {
        return new EloquentDteDocumentRepository(
            new DteDocumentPersistenceMapper()
        );
    }

    private function crearDocumento(string $status): int
    {
        return (int) DB::table('dte_documents')
            ->insertGetId([
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function crearDispatch(
        int $documentId,
        string $status,
        ?string $uploadStatusCode = null,
        int $retryCount = 0,
        ?string $nextRetryAt = null
    ): int {
        return (int) DB::table('sii_dispatches')
            ->insertGetId([
                'dte_document_id' => $documentId,
                'status' => $status,
                'upload_status_code' => $uploadStatusCode,
                'retry_count' => $retryCount,
                'next_retry_at' => $nextRetryAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
    public function test_excluye_signed_cuyo_ultimo_dispatch_es_failed(): void
    {
        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            $documentId,
            DispatchStatus::FAILED->value
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }
    public function test_incluye_signed_con_failed_retryable_cuyo_plazo_ya_vencio(): void
    {
        Carbon::setTestNow(
            '2026-09-25 03:00:00'
        );

        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            documentId: $documentId,
            status: DispatchStatus::FAILED->value,
            uploadStatusCode: null,
            retryCount: 1,
            nextRetryAt: '2026-09-25 02:59:59'
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertContains(
            $documentId,
            $ids
        );
    }
    public function test_excluye_failed_retryable_cuyo_plazo_aun_no_vence(): void
    {
        Carbon::setTestNow(
            '2026-09-25 03:00:00'
        );

        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            documentId: $documentId,
            status: DispatchStatus::FAILED->value,
            uploadStatusCode: null,
            retryCount: 1,
            nextRetryAt: '2026-09-25 03:00:01'
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }

    public function test_incluye_el_ultimo_retry_programado_al_alcanzar_el_maximo(): void
    {
        Carbon::setTestNow(
            '2026-09-25 03:00:00'
        );

        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        $documentId = $this->crearDocumento(
            DteStatus::SIGNED->value
        );

        $this->crearDispatch(
            documentId: $documentId,
            status: DispatchStatus::FAILED->value,
            uploadStatusCode: null,
            retryCount: 3,
            nextRetryAt: '2026-09-25 02:59:59'
        );

        $ids = $this->repository()
            ->findIdsEligibleForAutomation(
                [DteStatus::SIGNED->value],
                50
            );

        $this->assertContains(
            $documentId,
            $ids
        );
    }
    public function test_documento_sin_retry_interno_sigue_siendo_elegible(): void
    {
        $documentId =
            $this->crearDocumento(
                DteStatus::FOLIO_ASSIGNED->value
            );

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::FOLIO_ASSIGNED->value,
                    ],
                    50
                );

        $this->assertContains(
            $documentId,
            $ids
        );
    }
    public function test_excluye_documento_con_retry_interno_cuyo_plazo_aun_no_vence(): void
    {
        $documentId =
            $this->crearDocumento(
                DteStatus::FOLIO_ASSIGNED->value
            );

        DB::table(
            'dte_documents'
        )
            ->where(
                'id',
                $documentId
            )
            ->update([
                'automation_retry_action' =>
                    'build_xml',

                'automation_retry_count' =>
                    1,

                'automation_next_retry_at' =>
                    now()
                        ->addMinutes(5)
                        ->format(
                            'Y-m-d H:i:s'
                        ),
            ]);

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::FOLIO_ASSIGNED->value,
                    ],
                    50
                );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }
    public function test_incluye_documento_con_retry_interno_cuyo_plazo_ya_vencio(): void
    {
        $documentId =
            $this->crearDocumento(
                DteStatus::FOLIO_ASSIGNED->value
            );

        DB::table(
            'dte_documents'
        )
            ->where(
                'id',
                $documentId
            )
            ->update([
                'automation_retry_action' =>
                    'build_xml',

                'automation_retry_count' =>
                    1,

                'automation_next_retry_at' =>
                    now()
                        ->subSecond()
                        ->format(
                            'Y-m-d H:i:s'
                        ),
            ]);

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::FOLIO_ASSIGNED->value,
                    ],
                    50
                );

        $this->assertContains(
            $documentId,
            $ids
        );
    }
    public function test_incluye_el_ultimo_retry_interno_programado_al_alcanzar_el_maximo(): void
    {
        $documentId =
            $this->crearDocumento(
                DteStatus::FOLIO_ASSIGNED->value
            );

        DB::table(
            'dte_documents'
        )
            ->where(
                'id',
                $documentId
            )
            ->update([
                'automation_retry_action' =>
                    'build_xml',

                'automation_retry_count' =>
                    3,

                'automation_next_retry_at' =>
                    now()
                        ->subSecond()
                        ->format(
                            'Y-m-d H:i:s'
                        ),
            ]);

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::FOLIO_ASSIGNED->value,
                    ],
                    50
                );

        $this->assertContains(
            $documentId,
            $ids
        );
    }
    public function test_excluye_retry_interno_programado_sin_next_retry_at(): void
    {
        $documentId =
            $this->crearDocumento(
                DteStatus::FOLIO_ASSIGNED->value
            );

        DB::table(
            'dte_documents'
        )
            ->where(
                'id',
                $documentId
            )
            ->update([
                'automation_retry_action' =>
                    'build_xml',

                'automation_retry_count' =>
                    1,

                'automation_next_retry_at' =>
                    null,
            ]);

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::FOLIO_ASSIGNED->value,
                    ],
                    50
                );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }
}