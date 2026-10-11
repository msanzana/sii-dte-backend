<?php

namespace App\Modules\Dte\Domain\ValueObjects;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;

final class DtePrintPlan
{
    /**
     * @param DtePrintPlanItem[] $items
     */
    public function __construct(
        private readonly int $dteType,
        private readonly PrintFormat $format,
        private readonly array $items,
    ) {}

    public function dteType(): int
    {
        return $this->dteType;
    }

    public function format(): PrintFormat
    {
        return $this->format;
    }

    /**
     * @return DtePrintPlanItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    public function totalPhysicalCopies(): int
    {
        return array_sum(
            array_map(
                static fn (
                    DtePrintPlanItem $item
                ): int => $item->quantity(),
                $this->items
            )
        );
    }

    public function contains(
        PrintCopyType $type
    ): bool {
        foreach ($this->items as $item) {
            if ($item->type() === $type) {
                return true;
            }
        }

        return false;
    }
}