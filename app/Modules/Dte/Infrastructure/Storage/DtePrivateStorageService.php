<?php
namespace App\Modules\Dte\Infrastructure\Storage;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DtePrivateStorageService
{
    public function storeFileFromPath(
        string $sourcePath,
        string $targetDirectory,
        string $targetFilename
    ): string {
        if (!File::exists($sourcePath)) {
            throw new RuntimeException("No existe el archivo temporal: {$sourcePath}");
        }

        $baseDirectory = config('dte.storage.base');
        $absoluteTargetDirectory = $baseDirectory . DIRECTORY_SEPARATOR . trim($targetDirectory, DIRECTORY_SEPARATOR);

        if (!File::isDirectory($absoluteTargetDirectory)) {
            File::makeDirectory($absoluteTargetDirectory, 0777, true);
        }

        $absoluteTargetPath = $absoluteTargetDirectory . DIRECTORY_SEPARATOR . $targetFilename;
        File::copy($sourcePath, $absoluteTargetPath);

        return $this->relativeToStoragePath($absoluteTargetPath);
    }

    public function storeString(
        string $contents,
        string $targetDirectory,
        string $targetFileName,
    ): string {
        $baseDirectory = config('dte.storage.base');
        $absoluteTargetDirectory = $baseDirectory . DIRECTORY_SEPARATOR . trim($targetDirectory, DIRECTORY_SEPARATOR);

        if (!File::isDirectory($absoluteTargetDirectory)) {
            File::makeDirectory($absoluteTargetDirectory, 0777, true);
        }

        $absoluteTargetPath = $absoluteTargetDirectory . DIRECTORY_SEPARATOR . $targetFileName;

        File::put($absoluteTargetPath, $contents);

        return $this->relativeToStoragePath($absoluteTargetPath);
    }

    private function relativeToStoragePath(string $absolutePath): string
    {
        $storageRoot = storage_path();
        $normalizedAbsolute = str_replace('\\', '/', $absolutePath);
        $normalizedStorage = str_replace('\\', '/', $storageRoot);

        if (str_starts_with($normalizedAbsolute, $normalizedStorage . '/')) {
            return substr($normalizedAbsolute, strlen($normalizedStorage) + 1);
        }

        return $normalizedAbsolute;
    }

    public function storeContents(
        string $contents,
        string $targetDirectory,
        string $targetFilename
    ): string {
        $targetDirectory = trim($targetDirectory, '/');
        $relativePath = $targetDirectory . '/' . $targetFilename;

        $stored = Storage::disk('local')->put($relativePath, $contents);

        if (!$stored) {
            throw new RuntimeException('No fue posible guardar el archivo en el storage privado.');
        }

        return $relativePath;
    }

    public function getContents(string $relativePath): ?string
    {
        $normalizedRelativePath = $this->normalizeRelativePath($relativePath);

        if (!Storage::disk('local')->exists($normalizedRelativePath)) {
            return null;
        }

        $contents = Storage::disk('local')->get($normalizedRelativePath);

        return ($contents === false || $contents === null) ? null : $contents;
    }

    public function resolveAbsolutePath(string $relativePath): string
    {
        $normalizedRelativePath = $this->normalizeRelativePath($relativePath);

        return Storage::disk('local')->path($normalizedRelativePath);
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $path = trim($relativePath);
        $path = str_replace(['\\'], '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?? '';

        $path = ltrim($path, '/');

        if ($path === '') {
            throw new \InvalidArgumentException('La ruta relativa no puede estar vacía.');
        }

        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException('La ruta relativa contiene segmentos no permitidos.');
        }

        return $path;
    }
}
