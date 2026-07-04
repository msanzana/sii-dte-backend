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


    public function storeContents(
        string $contents,
        string $targetDirectory,
        string $targetFilename
    ): string {
        $targetDirectory = trim($targetDirectory, '/');
        $relativePath = $targetDirectory . '/' . $targetFilename;

        $stored = \Illuminate\Support\Facades\Storage::disk('local')->put(
            $relativePath,
            $contents
        );

        if (!$stored) {
            throw new \RuntimeException('No fue posible guardar el archivo en el storage privado.');
        }

        return $relativePath;
    }
    public function getContents(string $relativePath): ?string
    {
        $fullPath = $this->resolveAbsolutePath($relativePath);

        if (!is_file($fullPath)) {
            return null;
        }

        $contents = file_get_contents($fullPath);

        return $contents === false ? null : $contents;
    }

    public function resolveAbsolutePath(string $relativePath): string
    {
        $normalizedRelativePath = $this->normalizeRelativePath($relativePath);

        return $this->baseStoragePath()
            . DIRECTORY_SEPARATOR
            . $normalizedRelativePath;
    }

    private function baseStoragePath(): string
    {
        /*
         * IMPORTANTE:
         * Si tu servicio actual guarda en otra raíz distinta,
         * ajusta SOLO esta línea para que coincida con tu implementación real.
         */
        return storage_path('app/private/dte');
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $path = trim($relativePath);

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        while (str_contains($path, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR)) {
            $path = str_replace(
                DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                $path
            );
        }

        $path = ltrim($path, DIRECTORY_SEPARATOR);

        if ($path === '') {
            throw new \InvalidArgumentException('La ruta relativa no puede estar vacía.');
        }

        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException('La ruta relativa contiene segmentos no permitidos.');
        }

        return $path;
    }
}

