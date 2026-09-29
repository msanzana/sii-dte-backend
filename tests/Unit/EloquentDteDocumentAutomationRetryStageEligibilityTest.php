<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentDteDocumentAutomationRetryStageEligibilityTest
    extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'dte.automation.document_retry.max_attempts',
            3
        );

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

                $table
                    ->string(
                        'automation_retry_action',
                        40
                    )
                    ->nullable();

                $table
                    ->unsignedTinyInteger(
                        'automation_retry_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'automation_next_retry_at'
                    )
                    ->nullable();

                $table->timestamps();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Tabla mínima de dispatches
        |--------------------------------------------------------------------------
        |
        | findIdsEligibleForAutomation también contiene reglas asociadas al
        | último dispatch de documentos SIGNED. Estos tests no usan SIGNED,
        | pero dejamos disponibles las columnas necesarias para que la query
        | completa pueda compilar correctamente.
        |
        */

        Schema::create(
            'sii_dispatches',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'dte_document_id'
                );

                $table->string(
                    'status',
                    40
                );

                $table
                    ->string(
                        'upload_status_code',
                        20
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'retry_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'next_retry_at'
                    )
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(
            'sii_dispatches'
        );

        Schema::dropIfExists(
            'dte_documents'
        );

        parent::tearDown();
    }

    public function test_incluye_xml_built_con_build_ted_vencido(): void
    {
        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::XML_BUILT->value,

                retryAction:
                    'build_ted',

                retryCount:
                    1,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::XML_BUILT->value,
                    ],
                    50
                );

        $this->assertContains(
            $documentId,
            $ids
        );
    }

    public function test_incluye_ted_built_con_sign_xml_vencido(): void
    {
        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::TED_BUILT->value,

                retryAction:
                    'sign_xml',

                retryCount:
                    1,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::TED_BUILT->value,
                    ],
                    50
                );

        $this->assertContains(
            $documentId,
            $ids
        );
    }

    public function test_incluye_needs_resend_con_build_xml_vencido(): void
    {
        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::NEEDS_RESEND->value,

                retryAction:
                    'build_xml',

                retryCount:
                    1,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::NEEDS_RESEND->value,
                    ],
                    50
                );

        $this->assertContains(
            $documentId,
            $ids
        );
    }

    public function test_excluye_folio_assigned_con_sign_xml_vencido(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Caso inconsistente
        |--------------------------------------------------------------------------
        |
        | FOLIO_ASSIGNED solamente puede continuar por build_xml.
        |
        | Aunque sign_xml sea una acción globalmente retryable y su fecha
        | esté vencida, este documento NO debe ser entregado al pipeline.
        |
        */

        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::FOLIO_ASSIGNED->value,

                retryAction:
                    'sign_xml',

                retryCount:
                    1,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

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

    public function test_excluye_xml_built_con_build_xml_vencido(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Caso inconsistente
        |--------------------------------------------------------------------------
        |
        | Una vez en XML_BUILT, la etapa que corresponde es build_ted.
        | No debe retroceder automáticamente a build_xml.
        |
        */

        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::XML_BUILT->value,

                retryAction:
                    'build_xml',

                retryCount:
                    1,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

        $ids =
            $this->repository()
                ->findIdsEligibleForAutomation(
                    [
                        DteStatus::XML_BUILT->value,
                    ],
                    50
                );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }

    public function test_excluye_retry_count_cero_aunque_tenga_action_y_fecha(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Estado inconsistente
        |--------------------------------------------------------------------------
        |
        | Un retry programado válido comienza en count = 1.
        |
        */

        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::FOLIO_ASSIGNED->value,

                retryAction:
                    'build_xml',

                retryCount:
                    0,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

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

    public function test_excluye_retry_count_superior_al_maximo(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Retry por encima del máximo
        |--------------------------------------------------------------------------
        |
        | max_attempts = 3.
        |
        | Un count = 4 nunca debe volver al pump aunque tenga fecha vencida.
        |
        */

        $documentId =
            $this->crearDocumento(
                status:
                    DteStatus::FOLIO_ASSIGNED->value,

                retryAction:
                    'build_xml',

                retryCount:
                    4,

                nextRetryAt:
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s')
            );

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

    private function crearDocumento(
        string $status,
        ?string $retryAction,
        int $retryCount,
        ?string $nextRetryAt
    ): int {
        return (int) DB::table(
            'dte_documents'
        )
            ->insertGetId(
                [
                    'status' =>
                        $status,

                    'automation_retry_action' =>
                        $retryAction,

                    'automation_retry_count' =>
                        $retryCount,

                    'automation_next_retry_at' =>
                        $nextRetryAt,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );
    }

    private function repository(): EloquentDteDocumentRepository
    {
        return new EloquentDteDocumentRepository(
            new DteDocumentPersistenceMapper()
        );
    }
}