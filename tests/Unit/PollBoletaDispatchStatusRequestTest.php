<?php

namespace Tests\Unit;

use App\Modules\Dte\Presentation\Http\Requests\PollBoletaDispatchStatusRequest;
use ReflectionMethod;
use Tests\TestCase;

class PollBoletaDispatchStatusRequestTest extends TestCase
{
    public function test_prepare_for_validation_mapea_dispatch_id_desde_la_ruta(): void
    {
        $request =
            PollBoletaDispatchStatusRequest::create(
                '/api/internal/dte/dispatches/39/poll-boleta-send-status',
                'POST'
            );

        $request->setRouteResolver(
            function () {
                return new class {
                    public function parameter(
                        string $key,
                        mixed $default = null
                    ): mixed {
                        return $key === 'dispatchId'
                            ? '39'
                            : $default;
                    }
                };
            }
        );

        $method =
            new ReflectionMethod(
                PollBoletaDispatchStatusRequest::class,
                'prepareForValidation'
            );

        $method->setAccessible(true);

        $method->invoke(
            $request
        );

        $this->assertSame(
            '39',
            $request->input('dispatch_id')
        );

        $this->assertNull(
            $request->input('document_id')
        );

        $rules =
            $request->rules();

        $this->assertArrayHasKey(
            'dispatch_id',
            $rules
        );

        $this->assertArrayNotHasKey(
            'document_id',
            $rules
        );
    }
}