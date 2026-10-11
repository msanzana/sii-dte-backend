<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417LayoutSelection;

final class DtePdf417PrintFormatSelectionService
{
    public function __construct(
        private readonly DtePrintFormatPhysicalProfileService $profileService,
        private readonly DtePdf417LayoutSelectionService $layoutSelectionService,
    ) {}

    public function select(
        string $tedXmlUtf8,
        PrintFormat $format,
        int $preferredColumns = 15,
    ): DtePdf417LayoutSelection {
        $profile =
            $this->profileService->resolve(
                $format
            );

        return $this->layoutSelectionService->select(
            tedXmlUtf8: $tedXmlUtf8,
            availableWidthMm:
                $profile->usableWidthMm(),
            preferredColumns:
                $preferredColumns,
        );
    }
}