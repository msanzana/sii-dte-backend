<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSiiDispatchRepository;
use ReflectionClass;
use Tests\TestCase;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\SiiDispatchPersistenceMapper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EloquentSiiDispatchRepositoryTest extends TestCase
{
    public function test_find_by_id_for_update_expone_el_contrato_y_usa_lock_for_update(): void
    {
        $interfaceReflection = new ReflectionClass(
            SiiDispatchRepositoryInterface::class
        );

        $this->assertTrue(
            $interfaceReflection->hasMethod(
                'findByIdForUpdate'
            ),
            'SiiDispatchRepositoryInterface debe exponer findByIdForUpdate().'
        );

        $repositoryReflection = new ReflectionClass(
            EloquentSiiDispatchRepository::class
        );

        $this->assertTrue(
            $repositoryReflection->hasMethod(
                'findByIdForUpdate'
            ),
            'EloquentSiiDispatchRepository debe implementar findByIdForUpdate().'
        );

        $method = $repositoryReflection->getMethod(
            'findByIdForUpdate'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $methodSource = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            '->lockForUpdate()',
            $methodSource,
            'findByIdForUpdate() debe utilizar bloqueo pesimista lockForUpdate().'
        );

        $this->assertStringContainsString(
            "->where('id', \$id)",
            $methodSource,
            'findByIdForUpdate() debe bloquear específicamente el dispatch solicitado.'
        );

        $this->assertStringContainsString(
            '->first()',
            $methodSource
        );

        $this->assertStringContainsString(
            '$this->mapper->toDomain($model)',
            $methodSource,
            'El modelo bloqueado debe convertirse a la entidad de dominio.'
        );
    }
    public function test_update_persiste_retry_count_y_next_retry_at(): void
    {
        $repositoryReflection = new ReflectionClass(
            EloquentSiiDispatchRepository::class
        );

        $method = $repositoryReflection->getMethod(
            'update'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $methodSource = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            "'retry_count' => \$dispatch->retryCount()",
            $methodSource,
            'update() debe persistir retry_count.'
        );

        $this->assertStringContainsString(
            "'next_retry_at' => \$dispatch->nextRetryAt()",
            $methodSource,
            'update() debe persistir next_retry_at.'
        );
    }
    public function test_update_persiste_realmente_retry_count_y_next_retry_at_en_base_de_datos(): void
    {
        Schema::dropIfExists('sii_dispatches');

        Schema::create('sii_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('batch_uuid');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('dte_document_id')->nullable();
            $table->string('environment');
            $table->string('transport_type');
            $table->string('status');
            $table->string('track_id')->nullable();
            $table->string('request_identifier')->nullable();
            $table->string('request_path')->nullable();
            $table->text('request_headers')->nullable();
            $table->string('request_body_path')->nullable();
            $table->integer('response_http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->string('upload_status_code')->nullable();
            $table->string('upload_status_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        try {
            $id = (int) DB::table('sii_dispatches')->insertGetId([
                'batch_uuid' => '11111111-1111-1111-1111-111111111111',
                'company_id' => 1,
                'dte_document_id' => 10,
                'environment' => 'cert',
                'transport_type' => 'soap_upload_factura',
                'status' => 'failed',
                'retry_count' => 0,
                'next_retry_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $dispatch = new SiiDispatch(
                id: $id,
                batchUuid: '11111111-1111-1111-1111-111111111111',
                companyId: 1,
                dteDocumentId: 10,
                environment: 'cert',
                transportType: 'soap_upload_factura',
                status: 'failed',
                retryCount: 3,
                nextRetryAt: '2026-09-24 05:30:00',
            );

            $repository = new EloquentSiiDispatchRepository(
                new SiiDispatchPersistenceMapper()
            );

            $saved = $repository->update($dispatch);

            $row = DB::table('sii_dispatches')
                ->where('id', $id)
                ->first();

            $this->assertNotNull($row);

            $this->assertSame(
                3,
                (int) $row->retry_count
            );

            $this->assertSame(
                '2026-09-24 05:30:00',
                $row->next_retry_at
            );

            $this->assertSame(
                3,
                $saved->retryCount()
            );

            $this->assertSame(
                '2026-09-24 05:30:00',
                $saved->nextRetryAt()
            );
        } finally {
            Schema::dropIfExists('sii_dispatches');
        }
    }
}