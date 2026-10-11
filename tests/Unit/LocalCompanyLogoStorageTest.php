<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Storage\LocalCompanyLogoStorage;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

final class LocalCompanyLogoStorageTest extends TestCase
{
    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl9ZQAAAABJRU5ErkJggg==',
            true
        );
    }

    public function test_guarda_logo_png_en_directorio_de_empresa(): void
    {
        Storage::fake('local');

        $service =
            new LocalCompanyLogoStorage(
                new DtePrivateStorageService()
            );

        $bytes =
            $this->pngBytes();

        $result =
            $service->store(
                companyId:
                    15,
                contents:
                    $bytes,
                declaredMimeType:
                    'image/png',
            );

        $this->assertSame(
            'image/png',
            $result->mimeType()
        );

        $this->assertSame(
            hash('sha256', $bytes),
            $result->sha256()
        );

        $this->assertStringStartsWith(
            'dte/companies/15/branding/logo_',
            $result->path()
        );

        Storage::disk('local')
            ->assertExists(
                $result->path()
            );
    }

    public function test_rechaza_contenido_que_no_es_imagen(): void
    {
        Storage::fake('local');

        $service =
            new LocalCompanyLogoStorage(
                new DtePrivateStorageService()
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->store(
            companyId:
                1,
            contents:
                'esto-no-es-una-imagen',
            declaredMimeType:
                'image/png',
        );
    }

    public function test_rechaza_mime_declarado_distinto_al_real(): void
    {
        Storage::fake('local');

        $service =
            new LocalCompanyLogoStorage(
                new DtePrivateStorageService()
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->store(
            companyId:
                1,
            contents:
                $this->pngBytes(),
            declaredMimeType:
                'image/jpeg',
        );
    }

    public function test_elimina_logo_de_forma_idempotente(): void
    {
        Storage::fake('local');

        $service =
            new LocalCompanyLogoStorage(
                new DtePrivateStorageService()
            );

        $result =
            $service->store(
                companyId:
                    1,
                contents:
                    $this->pngBytes(),
                declaredMimeType:
                    'image/png',
            );

        $service->delete(
            $result->path()
        );

        Storage::disk('local')
            ->assertMissing(
                $result->path()
            );

        $service->delete(
            $result->path()
        );

        $this->assertTrue(true);
    }
}