<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentDteDocumentStatusQueryEligibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
                        'last_error_code',
                        40
                    )
                    ->nullable();

                $table
                    ->unsignedTinyInteger(
                        'status_query_retry_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'status_query_next_at'
                    )
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('dte_documents');

        parent::tearDown();
    }

    public function test_excluye_sent_con_proxima_consulta_futura(): void
    {
        Carbon::setTestNow(
            '2026-09-30 21:30:00'
        );

        config()->set(
            'dte.automation.document_status_retry.max_attempts',
            3
        );

        $normalId = (int) DB::table(
            'dte_documents'
        )
            ->insertGetId([
                'status' =>
                    DteStatus::SENT->value,

                'last_error_code' =>
                    null,

                'status_query_retry_count' =>
                    0,

                'status_query_next_at' =>
                    null,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $futureRetryId = (int) DB::table(
            'dte_documents'
        )
            ->insertGetId([
                'status' =>
                    DteStatus::SENT->value,

                'last_error_code' =>
                    'FAU',

                'status_query_retry_count' =>
                    1,

                'status_query_next_at' =>
                    now()
                        ->addMinutes(5)
                        ->format('Y-m-d H:i:s'),

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $ids = $this->repository()
            ->findIdsEligibleForDocumentStatusQuery(
                50
            );

        $this->assertContains(
            $normalId,
            $ids,
            'Un SENT sin retry pendiente debe seguir siendo elegible.'
        );

        $this->assertNotContains(
            $futureRetryId,
            $ids,
            'Un FAU cuya próxima consulta aún no vence no debe volver al pump.'
        );
    }

    private function repository(): EloquentDteDocumentRepository
    {
        return new EloquentDteDocumentRepository(
            new DteDocumentPersistenceMapper()
        );
    }
    public function test_excluye_retry_de_consulta_que_supera_el_maximo(): void
    {
        Carbon::setTestNow(
            '2026-10-01 00:15:00'
        );

        config()->set(
            'dte.automation.document_status_retry.max_attempts',
            3
        );

        $exhaustedId = (int) DB::table(
            'dte_documents'
        )
            ->insertGetId([
                'status' =>
                    DteStatus::SENT->value,

                'last_error_code' =>
                    'FAU',

                'status_query_retry_count' =>
                    4,

                'status_query_next_at' =>
                    now()
                        ->subMinute()
                        ->format('Y-m-d H:i:s'),

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $ids = $this->repository()
            ->findIdsEligibleForDocumentStatusQuery(
                50
            );

        $this->assertNotContains(
            $exhaustedId,
            $ids,
            'Un retry de consulta que supera max_attempts no debe volver al pump.'
        );
    }
    public function test_incluye_el_ultimo_retry_cuando_alcanza_el_maximo_y_ya_vencio(): void
    {
        Carbon::setTestNow(
            '2026-10-01 00:30:00'
        );

        config()->set(
            'dte.automation.document_status_retry.max_attempts',
            3
        );

        $lastRetryId = (int) DB::table(
            'dte_documents'
        )
            ->insertGetId([
                'status' =>
                    DteStatus::SENT->value,

                'last_error_code' =>
                    'FAU',

                'status_query_retry_count' =>
                    3,

                'status_query_next_at' =>
                    now()
                        ->subSecond()
                        ->format('Y-m-d H:i:s'),

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $ids = $this->repository()
            ->findIdsEligibleForDocumentStatusQuery(
                50
            );

        $this->assertContains(
            $lastRetryId,
            $ids,
            'El último retry permitido debe seguir siendo ejecutable cuando su plazo ya venció.'
        );
    }
    public function test_excluye_retry_programado_sin_next_at(): void
    {
        $documentId =
            (int) DB::table('dte_documents')
                ->insertGetId([
                    'status' =>
                        DteStatus::SENT->value,

                    'status_query_retry_count' =>
                        1,

                    'status_query_next_at' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

        $ids =
            $this->repository()
                ->findIdsEligibleForDocumentStatusQuery(
                    50
                );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }

    public function test_excluye_count_cero_si_tiene_next_at(): void
    {
        Carbon::setTestNow(
            '2026-10-01 23:30:00'
        );

        $documentId =
            (int) DB::table('dte_documents')
                ->insertGetId([
                    'status' =>
                        DteStatus::SENT->value,

                    'status_query_retry_count' =>
                        0,

                    'status_query_next_at' =>
                        '2026-10-01 23:29:00',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

        $ids =
            $this->repository()
                ->findIdsEligibleForDocumentStatusQuery(
                    50
                );

        $this->assertNotContains(
            $documentId,
            $ids
        );
    }
}