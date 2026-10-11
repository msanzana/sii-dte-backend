<?php

namespace App\Modules\Dte\Domain\ValueObjects;

use App\Modules\Dte\Domain\Enums\PrintFormat;

final class DtePdfDocumentRenderResult
{
    public function __construct(
        private readonly string $pdfBytes,
        private readonly PrintFormat $format,
        private readonly int $pageCount,
    ) {
        if ($this->pdfBytes === '') {
            throw new \InvalidArgumentException(
                'El PDF generado no puede estar vacío.'
            );
        }

        if (!str_starts_with($this->pdfBytes, '%PDF-')) {
            throw new \InvalidArgumentException(
                'El contenido generado no corresponde a un documento PDF válido.'
            );
        }

        if ($this->pageCount < 1) {
            throw new \InvalidArgumentException(
                'El PDF generado debe contener al menos una página.'
            );
        }
    }

    public function pdfBytes(): string
    {
        return $this->pdfBytes;
    }

    public function mimeType(): string
    {
        return 'application/pdf';
    }

    public function format(): PrintFormat
    {
        return $this->format;
    }

    public function pageCount(): int
    {
        return $this->pageCount;
    }

    public function byteLength(): int
    {
        return strlen($this->pdfBytes);
    }

    public function sha256(): string
    {
        return hash(
            'sha256',
            $this->pdfBytes
        );
    }
}