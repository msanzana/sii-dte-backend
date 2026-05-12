<?php
namespace App\Modules\Dte\Domain\Entities;

use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;

final class DteDocument
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $externalId,
        private readonly int $companyId,
        private readonly DteType $dteType,
        private readonly string $issueDate,
        private readonly string $status,
        private readonly ReceiverData $receiver,
        private readonly float $netAmount,
        private readonly float $exemptAmount,
        private readonly float $taxAmount,
        private readonly float $totalAmount,
        private readonly array $items,
        private readonly array $references = [],
        private readonly ?array $headerPayload = null,
        private readonly ?array $totalsPayload = null,
        private readonly ?array $rawInput = null,
        private readonly ?int $folio = null,
        private readonly ?string $siiEnvironment = null,
        private readonly ?string $unsignedXmlPath = null,
        private readonly ?string $signedXmlPath = null,
        private readonly ?string $tedXml = null,
        private readonly ?string $lastErrorCode = null,
        private readonly ?string $lastErrorMessage = null,
    )
    {}
    public function id(): ?int
    {
        return $this->id;
    }
    public function externalId(): string
    {
        return $this->externalId;
    }
    public function companyId(): int
    {
        return $this->companyId;
    }
    public function dteType(): DteType
    {
        return $this->dteType;
    }
    public function issueDate(): string
    {
        return $this->issueDate;
    }
    public function status(): string
    {
        return $this->status;
    }

    public function receiver(): ReceiverData
    {
        return $this->receiver;
    }
    public function netAmount(): float
    {
        return $this->netAmount;
    }
    public function exemptAmount(): float
    {
        return $this->exemptAmount;
    }
    public function taxAmount(): float
    {
        return $this->taxAmount;
    }
    public function totalAmount(): float
    {
        return $this->totalAmount;
    }
    public function items(): array
    {
        return $this->items;
    }
    public function references(): array
    {
        return $this->references;
    }
    public function headerPayload(): ?array
    {
        return $this->headerPayload;
    }
    public function totalsPayload(): ?array
    {
        return $this->totalsPayload;
    }
    public function rawInput(): ?array
    {
        return $this->rawInput;
    }
    public function folio(): ?int
    {
        return $this->folio;
    }
    public function siiEnvironment(): ?string
    {
        return $this->siiEnvironment;
    }
    public function unsignedXmlPath(): ?string
    {
        return $this->unsignedXmlPath;
    }
    public function signedXmlPath(): ?string
    {
        return $this->signedXmlPath;
    }
    public function tedXml(): ?string
    {
        return $this->tedXml;
    }
    public function lastErrorCode(): ?string
    {
        return $this->lastErrorCode;
    }
    public function lastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }
}
