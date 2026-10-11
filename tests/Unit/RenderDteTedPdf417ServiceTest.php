<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\RenderDteTedPdf417Service;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\RendererContracts\DtePdf417RendererInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use DomainException;
use Tests\TestCase;

final class RenderDteTedPdf417ServiceTest extends TestCase
{
    public function test_entrega_al_renderer_exactamente_el_ted_persistido(): void
    {
        $ted =
            '<TED version="1.0">'
            . '<DD><RSR>José Ñandú</RSR></DD>'
            . '<FRMT algoritmo="SHA1withRSA">'
            . 'QUJD'
            . '</FRMT>'
            . '</TED>';

        $renderer =
            new class
            implements DtePdf417RendererInterface {
                public ?string $receivedTed =
                    null;

                public ?int $receivedColumns =
                    null;

                public function render(
                    string $tedXmlUtf8,
                    int $columns = 15
                ): DtePdf417RenderResult {
                    $this->receivedTed =
                        $tedXmlUtf8;

                    $this->receivedColumns =
                        $columns;

                    return new DtePdf417RenderResult(
                        svg: '<svg/>',
                        mimeType:
                            'image/svg+xml',
                        columns: $columns,
                        rows: 10,
                        securityLevel: 5,
                        compactionCodeword: 901,
                        moduleAspectRatio: 3,
                        payloadByteLength:
                            strlen($tedXmlUtf8),
                        payloadSha256:
                            hash(
                                'sha256',
                                $tedXmlUtf8
                            ),
                    );
                }
            };

        $service =
            new RenderDteTedPdf417Service(
                $renderer
            );

        $result =
            $service->render(
                $this->document(
                    $ted
                ),
                12
            );

        $this->assertSame(
            $ted,
            $renderer->receivedTed
        );

        $this->assertSame(
            12,
            $renderer->receivedColumns
        );

        $this->assertSame(
            '<svg/>',
            $result->svg()
        );
    }

    public function test_rechaza_documento_sin_ted_persistido(): void
    {
        $renderer =
            new class
            implements DtePdf417RendererInterface {
                public function render(
                    string $tedXmlUtf8,
                    int $columns = 15
                ): DtePdf417RenderResult {
                    throw new \RuntimeException(
                        'El renderer no debería ejecutarse.'
                    );
                }
            };

        $service =
            new RenderDteTedPdf417Service(
                $renderer
            );

        $this->expectException(
            DomainException::class
        );

        $service->render(
            $this->document(null)
        );
    }

    private function document(
        ?string $tedXml
    ): DteDocument {
        return new DteDocument(
            id: 100,
            externalId:
                '00000000-0000-4000-8000-000000000100',
            companyId: 1,
            dteType:
                DteType::FACTURA_ELECTRTONICA,
            issueDate: '2026-10-09',
            status: 'signed',
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba',
                giro: 'SERVICIOS',
                address: 'Dirección prueba',
                cityId: 1,
                email: null,
            ),
            netAmount: 1000,
            exemptAmount: 0,
            taxAmount: 190,
            totalAmount: 1190,
            items: [],
            references: [],
            headerPayload: [],
            totalsPayload: null,
            rawInput: null,
            folio: 100,
            siiEnvironment: 'cert',
            unsignedXmlPath:
                'companies/1/dte/33/100/unsigned.xml',
            signedXmlPath:
                'companies/1/dte/33/100/signed.xml',
            tedXml: $tedXml,
        );
    }
}