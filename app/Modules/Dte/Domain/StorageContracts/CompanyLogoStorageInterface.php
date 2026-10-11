<?php

namespace App\Modules\Dte\Domain\StorageContracts;

use App\Modules\Dte\Domain\ValueObjects\CompanyLogoFile;

interface CompanyLogoStorageInterface
{
    public function store(
        int $companyId,
        string $contents,
        string $declaredMimeType
    ): CompanyLogoFile;

    public function getContents(
        string $relativePath
    ): ?string;

    public function delete(
        string $relativePath
    ): void;
}