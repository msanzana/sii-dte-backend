<?php

namespace Tests\Unit;

use App\Modules\Dte\Presentation\Http\Requests\StoreDteDocumentRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StoreDteDocumentTypeCatalogBoundaryTest extends TestCase
{
    public function test_form_request_no_mantiene_whitelist_hardcodeada_de_tipos_dte(): void
    {
        $request = new StoreDteDocumentRequest();

        foreach ([110, 999] as $dteType) {
            $validator = Validator::make(
                $this->validPayload($dteType),
                $request->rules()
            );

            $this->assertFalse(
                $validator->fails(),
                "El FormRequest rechazó estructuralmente el DTE {$dteType}: "
                . json_encode($validator->errors()->toArray())
            );
        }
    }

    public function test_form_request_sigue_exigiendo_que_dte_type_sea_entero(): void
    {
        $request = new StoreDteDocumentRequest();

        $validator = Validator::make(
            $this->validPayload('NO-ES-UN-ENTERO'),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertArrayHasKey(
            'dte_type',
            $validator->errors()->toArray()
        );
    }

    private function validPayload(
        int|string $dteType
    ): array {
        return [
            'dte_type' => $dteType,

            'issue_date' => '2026-10-06',

            'receiver' => [
                'document' => '76123456-7',
                'name' => 'Receptor Prueba',
                'giro' => 'Comercio',
                'address' => 'Dirección de prueba 123',
                'email' => 'receptor@example.com',
            ],

            'items' => [
                [
                    'name' => 'Producto de prueba',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'tax_exempt' => false,
                ],
            ],
        ];
    }
    public function test_form_request_no_mantiene_whitelist_en_tipo_dte_propuesto(): void
    {
        $request = new StoreDteDocumentRequest();

        $payload = $this->validPayload(110);

        $payload['external_system_id'] = 1;
        $payload['proposed_folio'] = 15;

        /*
        * El FormRequest ya no debe decidir qué códigos DTE
        * son válidos funcionalmente.
        */
        $payload['proposed_sii_document_type'] = 110;

        $validator = Validator::make(
            $payload,
            $request->rules()
        );

        $this->assertFalse(
            $validator->fails(),
            'El FormRequest rechazó proposed_sii_document_type=110: '
            . json_encode(
                $validator->errors()->toArray()
            )
        );
    }
}