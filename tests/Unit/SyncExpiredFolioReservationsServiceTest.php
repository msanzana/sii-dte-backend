<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\DeactivateFolioReservationService;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Application\Services\SyncExpiredFolioReservationsService;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use Mockery;
use Tests\TestCase;

final class SyncExpiredFolioReservationsServiceTest extends TestCase
{
    public function test_consulta_reservas_expiradas_con_fecha_formateada(): void
    {
        $reservationRepository = Mockery::mock(
            FolioReservationRepositoryInterface::class
        );

        $detailRepository = Mockery::mock(
            FolioDetailRepositoryInterface::class
        );

        $eventRepository = Mockery::mock(
            FolioDetailEventRepositoryInterface::class
        );

        $statusRepository = Mockery::mock(
            FolioStatusRepositoryInterface::class
        );

        $logRepository = Mockery::mock(
            IntegrationLogRepositoryInterface::class
        );

        $cafRepository = Mockery::mock(
            SiiCafRepositoryInterface::class
        );

        $recalculateService = new RecalculateCafCountersService(
            cafRepository: $cafRepository,
            folioDetailRepository: $detailRepository,
        );

        $deactivationService = new DeactivateFolioReservationService(
            reservationRepository: $reservationRepository,
            detailRepository: $detailRepository,
            eventRepository: $eventRepository,
            statusRepository: $statusRepository,
            recalculateCafCountersService: $recalculateService,
            logRepository: $logRepository,
        );

        $reservationRepository
            ->shouldReceive('findExpiredActiveReferences')
            ->once()
            ->with(
                Mockery::on(
                    fn ($value): bool =>
                        is_string($value)
                        && preg_match(
                            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                            $value
                        ) === 1
                ),
                100
            )
            ->andReturn([]);

        $service = new SyncExpiredFolioReservationsService(
            reservationRepository: $reservationRepository,
            deactivationService: $deactivationService,
            logRepository: $logRepository,
        );

        $processed = $service->execute(100);

        $this->assertSame(0, $processed);
    }
    public function test_usa_el_id_de_la_referencia_como_reservation_id(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/Services/SyncExpiredFolioReservationsService.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "reservationId: (int) \$reference['id']",
            $source
        );

        $this->assertStringNotContainsString(
            "reservationId: (int) \$reference['reservation_id']",
            $source
        );
    }
}