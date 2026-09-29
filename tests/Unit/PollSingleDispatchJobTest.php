<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\PollSingleDispatchJob;
use App\Jobs\Dte\Automation\QuerySingleDocumentStatusJob;
use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusResultDto;
use App\Modules\Dte\Application\DTOs\PollSiiUploadStatusResultDto;
use App\Modules\Dte\Application\UseCases\Dispatch\PollBoletaDispatchStatusUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\PollSiiUploadStatusUseCase;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class PollSingleDispatchJobTest extends TestCase
{
    public function test_factura_se_enruta_al_polling_soap(): void
    {
        $source = file_get_contents(
            app_path('Jobs/Dte/Automation/PollSingleDispatchJob.php')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "'soap_upload_factura'",
            $source
        );

        $this->assertStringContainsString(
            'PollSiiUploadStatusUseCase',
            $source
        );

        $this->assertStringContainsString(
            'new PollSiiUploadStatusInputDto(',
            $source
        );

        $this->assertStringContainsString(
            '$pollSiiUploadStatusUseCase->execute(',
            $source
        );
    }

    public function test_boleta_se_enruta_al_polling_rest(): void
    {
        $source = file_get_contents(
            app_path('Jobs/Dte/Automation/PollSingleDispatchJob.php')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "'rest_upload_boleta'",
            $source
        );

        $this->assertStringContainsString(
            'PollBoletaDispatchStatusUseCase',
            $source
        );

        $this->assertStringContainsString(
            'new PollBoletaDispatchStatusInputDto(',
            $source
        );

        $this->assertStringContainsString(
            '$pollBoletaDispatchStatusUseCase->execute(',
            $source
        );
    }

    public function test_dispatch_processed_agenda_consulta_documental(): void
    {
        $source = file_get_contents(
            app_path('Jobs/Dte/Automation/PollSingleDispatchJob.php')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "\$result->status === 'processed'",
            $source
        );

        $this->assertStringContainsString(
            'QuerySingleDocumentStatusJob::dispatch(',
            $source
        );

        $this->assertStringContainsString(
            "config('dte.automation.delays.document_status_query_seconds', 60)",
            $source
        );

        $this->assertStringContainsString(
            "config('dte.automation.queues.document_status')",
            $source
        );
    }
}
