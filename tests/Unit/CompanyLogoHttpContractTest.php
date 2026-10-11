<?php

namespace Tests\Unit;

use App\Modules\Dte\Presentation\Http\Requests\UploadCompanyLogoRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CompanyLogoHttpContractTest extends TestCase
{
    public function test_request_limita_logo_a_png_o_jpeg_y_dos_mb(): void
    {
        $request =
            new UploadCompanyLogoRequest();

        $rules =
            $request->rules();

        $this->assertArrayHasKey(
            'logo',
            $rules
        );

        $this->assertContains(
            'required',
            $rules['logo']
        );

        $this->assertContains(
            'file',
            $rules['logo']
        );

        $this->assertContains(
            'mimetypes:image/png,image/jpeg',
            $rules['logo']
        );

        $this->assertContains(
            'max:2048',
            $rules['logo']
        );

        $this->assertContains(
            'dimensions:max_width=4096,max_height=4096',
            $rules['logo']
        );
    }

    public function test_controlador_resuelve_empresa_exclusivamente_desde_jwt(): void
    {
        $reflection =
            new ReflectionClass(
                \App\Modules\Dte\Presentation\Http\Controllers\CompanyLogoController::class
            );

        $source =
            file_get_contents(
                $reflection->getFileName()
            );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            "'auth_company_id'",
            $source
        );

        $this->assertStringNotContainsString(
            "route('companyId')",
            $source
        );

        $this->assertStringNotContainsString(
            "input('company_id')",
            $source
        );
    }

    public function test_rutas_de_logo_no_exponen_company_id(): void
    {
        $routeFile =
            dirname(
                __DIR__,
                2
            )
            . '/app/Modules/Dte/Presentation/Http/Routes/api.php';

        $source =
            file_get_contents(
                $routeFile
            );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            "'/company/logo'",
            $source
        );

        $this->assertStringNotContainsString(
            "'/companies/{companyId}/logo'",
            $source
        );
    }
}