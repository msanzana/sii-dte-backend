<?php

namespace App\Modules\Dte\Infrastructure\Crypto;

use RuntimeException;
use Symfony\Component\Process\Process;

class LegacyPfxConverter
{
    public function convertToModernPfx(
        string $legacyPfxContents,
        string $oldPassword,
        string $newPassword
    ): string {
        $baseDir = storage_path('app/dte/tmp-pfx-converter');

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0700, true);
        }

        $id = bin2hex(random_bytes(16));

        $legacyPath = $baseDir . DIRECTORY_SEPARATOR . $id . '_legacy.pfx';
        $pemPath = $baseDir . DIRECTORY_SEPARATOR . $id . '_temp.pem';
        $modernPath = $baseDir . DIRECTORY_SEPARATOR . $id . '_modern.pfx';

        file_put_contents($legacyPath, $legacyPfxContents);

        try {
            $opensslBinary = config('dte.openssl_binary', 'openssl');
            $opensslModules = config('dte.openssl_modules');

            $providerArguments = $this->buildProviderArguments($opensslModules);

            $extractCommand = array_merge([
                $opensslBinary,
                'pkcs12',
            ], $providerArguments, [
                '-legacy',
                '-in',
                $legacyPath,
                '-out',
                $pemPath,
                '-nodes',
                '-passin',
                'env:PFX_OLD_PASSWORD',
            ]);

            $extract = new Process($extractCommand);

            $extract->setEnv($this->buildProcessEnvironment([
                'PFX_OLD_PASSWORD' => $oldPassword,
            ]));

            $extract->setTimeout(60);
            $extract->run();

            if (!$extract->isSuccessful()) {
                logger()->error('Error al extraer PFX legacy.', [
                    'command' => $extract->getCommandLine(),
                    'error_output' => $extract->getErrorOutput(),
                ]);

                throw new RuntimeException(
                    'No fue posible extraer el PFX legacy: ' . trim($extract->getErrorOutput())
                );
            }

            $exportCommand = array_merge([
                $opensslBinary,
                'pkcs12',
            ], $providerArguments, [
                '-export',
                '-in',
                $pemPath,
                '-out',
                $modernPath,
                '-certpbe',
                'AES-256-CBC',
                '-keypbe',
                'AES-256-CBC',
                '-macalg',
                'sha256',
                '-passout',
                'env:PFX_NEW_PASSWORD',
            ]);

            $export = new Process($exportCommand);

            $export->setEnv($this->buildProcessEnvironment([
                'PFX_NEW_PASSWORD' => $newPassword,
            ]));

            $export->setTimeout(60);
            $export->run();

            if (!$export->isSuccessful()) {
                logger()->error('Error al generar PFX moderno.', [
                    'command' => $export->getCommandLine(),
                    'error_output' => $export->getErrorOutput(),
                ]);

                throw new RuntimeException(
                    'No fue posible generar el PFX moderno: ' . trim($export->getErrorOutput())
                );
            }

            $modernPfxContents = file_get_contents($modernPath);

            if ($modernPfxContents === false || $modernPfxContents === '') {
                throw new RuntimeException('El PFX moderno fue generado vacío.');
            }

            return $modernPfxContents;
        } finally {
            foreach ([$legacyPath, $pemPath, $modernPath] as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function buildProviderArguments(?string $opensslModules): array
    {
        $arguments = [];

        if ($opensslModules !== null && trim($opensslModules) !== '') {
            $arguments[] = '-provider-path';
            $arguments[] = $opensslModules;
        }

        $arguments[] = '-provider';
        $arguments[] = 'default';

        $arguments[] = '-provider';
        $arguments[] = 'legacy';

        return $arguments;
    }

    private function buildProcessEnvironment(array $extra): array
    {
        $environment = $extra;

        $opensslModules = config('dte.openssl_modules');

        if ($opensslModules !== null && trim($opensslModules) !== '') {
            $environment['OPENSSL_MODULES'] = $opensslModules;
        }

        return $environment;
    }
}