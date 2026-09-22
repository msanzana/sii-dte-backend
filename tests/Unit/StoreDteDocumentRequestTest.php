<?php

namespace Tests\Unit;

use App\Modules\Dte\Presentation\Http\Requests\StoreDteDocumentRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreDteDocumentRequestTest extends TestCase
{
    public function test_boleta_39_exige_ind_servicio(): void
    {
        $data = [
            'dte_type' => 39,
            'issue_date' => '2026-09-18',

            'receiver' => [
                'document' => '11111111-1',
                'name' => 'Cliente prueba',
            ],

            'items' => [
                [
                    'name' => 'Producto prueba',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],

            'header_payload' => [],
        ];

        $request = StoreDteDocumentRequest::create(
            '/api/internal/dte/documents',
            'POST',
            $data
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertArrayHasKey(
            'header_payload.ind_servicio',
            $validator->errors()->toArray()
        );
    }

    public function test_boleta_39_acepta_ind_servicio_valido(): void
    {
        $data = [
            'dte_type' => 39,
            'issue_date' => '2026-09-18',

            'receiver' => [
                'document' => '11111111-1',
                'name' => 'Cliente prueba',
            ],

            'items' => [
                [
                    'name' => 'Producto prueba',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],

            'header_payload' => [
                'ind_servicio' => 3,
            ],
        ];

        $request = StoreDteDocumentRequest::create(
            '/api/internal/dte/documents',
            'POST',
            $data
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_PRETTY_PRINT
            )
        );
    }

    public function test_boleta_39_rechaza_ind_servicio_fuera_de_rango(): void
    {
        $data = [
            'dte_type' => 39,
            'issue_date' => '2026-09-18',

            'receiver' => [
                'document' => '11111111-1',
                'name' => 'Cliente prueba',
            ],

            'items' => [
                [
                    'name' => 'Producto prueba',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],

            'header_payload' => [
                'ind_servicio' => 5,
            ],
        ];

        $request = StoreDteDocumentRequest::create(
            '/api/internal/dte/documents',
            'POST',
            $data
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertArrayHasKey(
            'header_payload.ind_servicio',
            $validator->errors()->toArray()
        );
    }
    public function test_boleta_41_tambien_exige_ind_servicio(): void
    {
        $data = [
            'dte_type' => 41,
            'issue_date' => '2026-09-18',

            'receiver' => [
                'document' => '11111111-1',
                'name' => 'Cliente prueba',
            ],

            'items' => [
                [
                    'name' => 'Producto prueba',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],

            'header_payload' => [],
        ];

        $request = StoreDteDocumentRequest::create(
            '/api/internal/dte/documents',
            'POST',
            $data
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertArrayHasKey(
            'header_payload.ind_servicio',
            $validator->errors()->toArray()
        );
    }

    public function test_factura_33_no_exige_ind_servicio(): void
    {
        $data = [
            'dte_type' => 33,
            'issue_date' => '2026-09-18',

            'receiver' => [
                'document' => '11111111-1',
                'name' => 'Cliente prueba',
                'address' => 'Dirección prueba',
            ],

            'items' => [
                [
                    'name' => 'Producto prueba',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $request = StoreDteDocumentRequest::create(
            '/api/internal/dte/documents',
            'POST',
            $data
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_PRETTY_PRINT
            )
        );
    }
}