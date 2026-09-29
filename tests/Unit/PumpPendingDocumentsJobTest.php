<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\AdvanceDteDocumentPipelineJob;
use App\Jobs\Dte\Automation\PumpPendingDocumentsJob;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class PumpPendingDocumentsJobTest extends TestCase
{
    public function test_respeta_el_limite_configurado_de_documentos_por_pump(): void
    {
        config([
            'cache.default' => 'array',
        ]);
        Queue::fake();

        config()->set(
            'dte.automation.limits.documents_per_pump',
            7
        );

        $repository = Mockery::mock(
            DteDocumentRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findIdsEligibleForAutomation')
            ->once()
            ->with(
                Mockery::on(function (array $statuses): bool {
                    $expected = [
                        DteStatus::READY_FOR_XML->value,
                        DteStatus::FOLIO_ASSIGNED->value,
                        DteStatus::NEEDS_RESEND->value,
                        DteStatus::XML_BUILT->value,
                        DteStatus::TED_BUILT->value,
                        DteStatus::SIGNED->value,
                    ];

                    sort($statuses);
                    sort($expected);

                    return $statuses === $expected;
                }),
                7
            )
            ->andReturn([
                10,
                20,
            ]);

        $job = new PumpPendingDocumentsJob();

        $job->handle($repository);

        Queue::assertPushed(
            AdvanceDteDocumentPipelineJob::class,
            2
        );
    }
    public function test_usa_una_seleccion_especifica_de_documentos_elegibles_para_automatizacion(): void
    {
        $jobSource = file_get_contents(
            app_path(
                'Jobs/Dte/Automation/PumpPendingDocumentsJob.php'
            )
        );

        $repositoryContractSource = file_get_contents(
            app_path(
                'Modules/Dte/Domain/RepositoryContracts/DteDocumentRepositoryInterface.php'
            )
        );

        $this->assertNotFalse($jobSource);
        $this->assertNotFalse($repositoryContractSource);

        $this->assertStringContainsString(
            'findIdsEligibleForAutomation',
            $jobSource,
            'PumpPendingDocumentsJob debe usar una selección específica de documentos elegibles.'
        );

        $this->assertStringContainsString(
            'findIdsEligibleForAutomation',
            $repositoryContractSource,
            'El repositorio de documentos debe exponer explícitamente la selección de documentos elegibles para automatización.'
        );
    }
}
