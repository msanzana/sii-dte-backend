<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePdf417RenderResult
{
    public function __construct(
        private readonly string $svg,
        private readonly string $mimeType,
        private readonly int $columns,
        private readonly int $rows,
        private readonly int $securityLevel,
        private readonly int $compactionCodeword,
        private readonly int $moduleAspectRatio,
        private readonly int $payloadByteLength,
        private readonly string $payloadSha256,
    ) {
        if (trim($this->svg) === '') {
            throw new \InvalidArgumentException(
                'El SVG del PDF417 no puede estar vacío.'
            );
        }

        if (
            !in_array(
                $this->compactionCodeword,
                [901, 924],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'El PDF417 debe utilizar Byte Compaction Mode.'
            );
        }

        if ($this->payloadByteLength < 1) {
            throw new \InvalidArgumentException(
                'El payload del PDF417 no puede estar vacío.'
            );
        }
    }

    public function svg(): string
    {
        return $this->svg;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function columns(): int
    {
        return $this->columns;
    }

    public function rows(): int
    {
        return $this->rows;
    }

    public function securityLevel(): int
    {
        return $this->securityLevel;
    }

    public function compactionCodeword(): int
    {
        return $this->compactionCodeword;
    }

    public function moduleAspectRatio(): int
    {
        return $this->moduleAspectRatio;
    }

    public function payloadByteLength(): int
    {
        return $this->payloadByteLength;
    }

    public function payloadSha256(): string
    {
        return $this->payloadSha256;
    }
}