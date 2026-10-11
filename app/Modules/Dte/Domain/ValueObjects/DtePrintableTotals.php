<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePrintableTotals
{
    public function __construct(
        private readonly float $net,
        private readonly float $exempt,
        private readonly float $tax,
        private readonly float $total,
        private readonly ?string $totalInWords = null,
    ) {}

    public function net(): float { return $this->net; }
    public function exempt(): float { return $this->exempt; }
    public function tax(): float { return $this->tax; }
    public function total(): float { return $this->total; }
    public function totalInWords(): ?string
    {
        return $this->totalInWords;
    }
}