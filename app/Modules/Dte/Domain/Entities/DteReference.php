<?php
namespace App\Modules\Dte\Domain\Entities;
final class DteReference
{
    public function __construct(
        private readonly int $lineNumber,
        private readonly ?int $referencedDteType,
        private readonly ?int $referencedFolio,
        private readonly ?string $referencedIssueDate,
        private readonly ?int $referencedCode,
        private readonly ?string $reason,
        private readonly ?array $extraPayload = null,
    )
    {}
    public function lineNumber(): int
    {
        return $this->lineNumber;
    }
    public function referencedDteType(): ?int
    {
        return $this->referencedDteType;
    }
    public function referencedFolio(): ?int
    {
        return $this->referencedFolio;
    }
    public function referencedIssueDate(): ?string
    {
        return $this->referencedIssueDate;
    }
    public function referencedCode(): ?string
    {
        return $this->referencedCode;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function extraPayload(): ?array
    {
        return $this->extraPayload;
    }
}
