<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\ValueObjects\DtePrintFormatPhysicalProfile;

final class DtePrintFormatPhysicalProfileService
{
    public function resolve(
        PrintFormat $format
    ): DtePrintFormatPhysicalProfile {
        return match ($format) {
            PrintFormat::LETTER =>
                new DtePrintFormatPhysicalProfile(
                    format: $format,
                    paperWidthMm: 215.9,
                    leftMarginMm: 12.0,
                    rightMarginMm: 12.0,
                ),

            PrintFormat::TICKET_80 =>
                new DtePrintFormatPhysicalProfile(
                    format: $format,
                    paperWidthMm: 80.0,
                    leftMarginMm: 4.0,
                    rightMarginMm: 4.0,
                ),

            PrintFormat::TICKET_58 =>
                new DtePrintFormatPhysicalProfile(
                    format: $format,
                    paperWidthMm: 58.0,
                    leftMarginMm: 4.0,
                    rightMarginMm: 4.0,
                ),
        };
    }
}