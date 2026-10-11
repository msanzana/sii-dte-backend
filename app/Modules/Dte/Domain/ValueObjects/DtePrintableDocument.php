<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePrintableDocument
{
    /**
     * @param DtePrintableLineItem[] $items
     * @param DtePrintableReference[] $references
     */
    public function __construct(
        private readonly int $documentId,
        private readonly string $externalId,
        private readonly int $dteType,
        private readonly int $folio,
        private readonly string $issueDate,
        private readonly DtePrintableIssuer $issuer,
        private readonly DtePrintableReceiver $receiver,
        private readonly DtePrintableTotals $totals,
        private readonly array $items,
        private readonly array $references,
    ) {
        if ($this->folio < 1) {
            throw new \InvalidArgumentException(
                'El documento imprimible requiere un folio válido.'
            );
        }

        if ($this->items === []) {
            throw new \InvalidArgumentException(
                'El documento imprimible requiere al menos un ítem.'
            );
        }
    }

    public function documentId(): int { return $this->documentId; }
    public function externalId(): string { return $this->externalId; }
    public function dteType(): int { return $this->dteType; }
    public function folio(): int { return $this->folio; }
    public function issueDate(): string { return $this->issueDate; }
    public function issuer(): DtePrintableIssuer { return $this->issuer; }
    public function receiver(): DtePrintableReceiver { return $this->receiver; }
    public function totals(): DtePrintableTotals { return $this->totals; }

    /** @return DtePrintableLineItem[] */
    public function items(): array { return $this->items; }

    /** @return DtePrintableReference[] */
    public function references(): array { return $this->references; }
}