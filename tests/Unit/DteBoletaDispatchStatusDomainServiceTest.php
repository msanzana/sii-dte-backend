<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendStatusException;
use App\Modules\Dte\Domain\Services\DteBoletaDispatchStatusDomainService;
use Tests\TestCase;

final class DteBoletaDispatchStatusDomainServiceTest extends TestCase
{
    public function test_no_permite_polling_sobre_estados_terminales(): void
    {
        $service = new DteBoletaDispatchStatusDomainService();

        $terminalStatuses = [
            DispatchStatus::PROCESSED->value,
            DispatchStatus::ACCEPTED->value,
            DispatchStatus::REJECTED->value,
            DispatchStatus::FAILED->value,
            DispatchStatus::UPLOAD_REJECTED->value,
        ];

        foreach ($terminalStatuses as $status) {
            $dispatch = $this->createDispatch(
                status: $status
            );

            try {
                $service->assertCanPoll(
                    $dispatch
                );

                $this->fail(
                    "El estado {$status} no debe permitir un nuevo polling."
                );
            } catch (SiiBoletaSendStatusException $exception) {
                $this->assertStringContainsString(
                    $status,
                    $exception->getMessage()
                );
            }
        }
    }

    public function test_permite_polling_en_estados_operativos_con_track_id(): void
    {
        $service = new DteBoletaDispatchStatusDomainService();

        $pollableStatuses = [
            'sent',
            DispatchStatus::POLLING->value,
            DispatchStatus::UPLOAD_OK->value,
        ];

        foreach ($pollableStatuses as $status) {
            $dispatch = $this->createDispatch(
                status: $status
            );

            $service->assertCanPoll(
                $dispatch
            );

            $this->assertTrue(
                true
            );
        }
    }

    private function createDispatch(
        string $status,
        ?string $trackId = '1234567890'
    ): SiiDispatch
    {
        return new SiiDispatch(
            id: 1,
            batchUuid: 'test-batch-uuid',
            companyId: 1,
            dteDocumentId: 1,
            environment: 'cert',
            transportType: 'rest_upload_boleta',
            status: $status,
            trackId: $trackId,
        );
    }
}