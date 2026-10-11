<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\Services\DtePrintFormatPhysicalProfileService;
use Tests\TestCase;

final class DtePrintFormatPhysicalProfileServiceTest extends TestCase
{
    public function test_carta_calcula_ancho_util(): void
    {
        $profile =
            (new DtePrintFormatPhysicalProfileService())
                ->resolve(
                    PrintFormat::LETTER
                );

        $this->assertEqualsWithDelta(
            215.9,
            $profile->paperWidthMm(),
            0.0001
        );

        $this->assertEqualsWithDelta(
            191.9,
            $profile->usableWidthMm(),
            0.0001
        );
    }

    public function test_ticket_80_calcula_ancho_util(): void
    {
        $profile =
            (new DtePrintFormatPhysicalProfileService())
                ->resolve(
                    PrintFormat::TICKET_80
                );

        $this->assertEqualsWithDelta(
            72.0,
            $profile->usableWidthMm(),
            0.0001
        );
    }

    public function test_ticket_58_calcula_ancho_util(): void
    {
        $profile =
            (new DtePrintFormatPhysicalProfileService())
                ->resolve(
                    PrintFormat::TICKET_58
                );

        $this->assertEqualsWithDelta(
            50.0,
            $profile->usableWidthMm(),
            0.0001
        );
    }
}