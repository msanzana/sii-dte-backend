<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class CompanyLogoFile
{
    public function __construct(
        private readonly string $path,
        private readonly string $mimeType,
        private readonly string $sha256,
    ) {
        if (trim($this->path) === '') {
            throw new \InvalidArgumentException(
                'La ruta del logo no puede estar vacía.'
            );
        }

        if (
            !in_array(
                $this->mimeType,
                [
                    'image/png',
                    'image/jpeg',
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'El tipo MIME del logo no es válido.'
            );
        }

        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $this->sha256
            )
        ) {
            throw new \InvalidArgumentException(
                'El hash SHA-256 del logo no es válido.'
            );
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}