<?php

namespace App\Modules\Dte\Infrastructure\Storage;

use App\Modules\Dte\Domain\StorageContracts\CompanyLogoStorageInterface;
use App\Modules\Dte\Domain\ValueObjects\CompanyLogoFile;
use InvalidArgumentException;

final class LocalCompanyLogoStorage implements CompanyLogoStorageInterface
{
    private const MAX_BYTES = 2097152;
    private const MAX_WIDTH = 4096;
    private const MAX_HEIGHT = 4096;

    public function __construct(
        private readonly DtePrivateStorageService $storage,
    ) {}

    public function store(
        int $companyId,
        string $contents,
        string $declaredMimeType
    ): CompanyLogoFile {
        if ($companyId <= 0) {
            throw new InvalidArgumentException(
                'El identificador de empresa debe ser positivo.'
            );
        }

        if ($contents === '') {
            throw new InvalidArgumentException(
                'El archivo de logo está vacío.'
            );
        }

        if (
            strlen($contents)
            > self::MAX_BYTES
        ) {
            throw new InvalidArgumentException(
                'El logo excede el máximo permitido de 2 MB.'
            );
        }

        $imageInfo =
            @getimagesizefromstring(
                $contents
            );

        if ($imageInfo === false) {
            throw new InvalidArgumentException(
                'El archivo no contiene una imagen válida.'
            );
        }

        $detectedMimeType =
            strtolower(
                trim(
                    (string) (
                        $imageInfo['mime']
                        ?? ''
                    )
                )
            );

        $extensions = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
        ];

        if (
            !array_key_exists(
                $detectedMimeType,
                $extensions
            )
        ) {
            throw new InvalidArgumentException(
                'Sólo se permiten logos PNG o JPEG.'
            );
        }

        $normalizedDeclaredMime =
            strtolower(
                trim(
                    $declaredMimeType
                )
            );

        if (
            $normalizedDeclaredMime
            !== $detectedMimeType
        ) {
            throw new InvalidArgumentException(
                'El tipo MIME declarado no coincide con el contenido real de la imagen.'
            );
        }

        $width =
            (int) (
                $imageInfo[0]
                ?? 0
            );

        $height =
            (int) (
                $imageInfo[1]
                ?? 0
            );

        if (
            $width <= 0
            || $height <= 0
        ) {
            throw new InvalidArgumentException(
                'No fue posible determinar las dimensiones del logo.'
            );
        }

        if (
            $width > self::MAX_WIDTH
            || $height > self::MAX_HEIGHT
        ) {
            throw new InvalidArgumentException(
                'El logo excede las dimensiones máximas de 4096x4096 píxeles.'
            );
        }

        $sha256 =
            hash(
                'sha256',
                $contents
            );

        $extension =
            $extensions[
                $detectedMimeType
            ];

        $targetDirectory =
            sprintf(
                'dte/companies/%d/branding',
                $companyId
            );

        $targetFilename =
            sprintf(
                'logo_%s.%s',
                $sha256,
                $extension
            );

        $relativePath =
            $this->storage
                ->storeContents(
                    contents:
                        $contents,
                    targetDirectory:
                        $targetDirectory,
                    targetFilename:
                        $targetFilename,
                );

        return new CompanyLogoFile(
            path:
                $relativePath,
            mimeType:
                $detectedMimeType,
            sha256:
                $sha256,
        );
    }
    public function getContents(
        string $relativePath
    ): ?string {
        return $this->storage
            ->getContents(
                $relativePath
            );
    }
    public function delete(
        string $relativePath
    ): void {
        $this->storage
            ->delete(
                $relativePath
            );
    }
}