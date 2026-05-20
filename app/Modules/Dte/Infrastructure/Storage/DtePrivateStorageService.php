<?php
namespace App\Modules\Dte\Infrastructure\Storage;

use Illuminate\Support\Facades\File;
use RuntimeException;

class DtePrivateStorageService
{
    public function storeFileFromPath(
        string $sourcePath,
        string $targetDirectory,
        string $targetFilename
    ): string {
        if (!File::exists($sourcePath))
        {
            throw new RuntimeException("No existe el archivo temporal: {$sourcePath}");
        }

        $baseDirectory = config('dte.storage.base');
        $absoluteTargetDirectory = $baseDirectory . DIRECTORY_SEPARATOR . trim($targetDirectory, DIRECTORY_SEPARATOR);

        if (!File::isDirectory($absoluteTargetDirectory)) {
            File::makeDirectory($absoluteTargetDirectory,0777, true);
        }

        $absoluteTargetPath = $absoluteTargetDirectory . DIRECTORY_SEPARATOR . $targetFilename;
        File::copy($sourcePath, $absoluteTargetPath);

        return $this->relativeToStoragePath($absoluteTargetPath);
    }

    public function storeString(
        string $contents,
        string $targetDirectory,
        string $targetFileName,
    ): string
    {
        $baseDirectory = config('dte.storage.base');
        $absoluteTargetDirectory = $baseDirectory . DIRECTORY_SEPARATOR . trim($targetDirectory, DIRECTORY_SEPARATOR);

        if(!File::isDirectory($absoluteTargetDirectory))
        {
            File::makeDirectory($absoluteTargetDirectory,0777,true);
        }

        $absoluteTargetPath = $absoluteTargetDirectory . DIRECTORY_SEPARATOR . $targetFileName;

        File::put($absoluteTargetPath, $contents);

        return $this->relativeToStoragePath($absoluteTargetPath);
    }

    private function relativeToStoragePath(string $absolutePath): string{
        $storageRoot = storage_path();
        $normalizedAbsolute = str_replace('\\', '/', $absolutePath);
        $normalizedStorage = str_replace('\\', '/', $storageRoot);

        if(str_starts_with($normalizedAbsolute, $normalizedStorage. '/'))
        {
            return substr($normalizedAbsolute, strlen($normalizedStorage) + 1);
        }

        return $normalizedAbsolute;
    }
}
