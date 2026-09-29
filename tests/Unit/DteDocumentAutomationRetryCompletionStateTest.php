<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

final class DteDocumentAutomationRetryCompletionStateTest extends TestCase
{
    public function test_build_xml_exitoso_limpia_el_retry_interno(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::FOLIO_ASSIGNED->value
            )
            ->withAutomationRetryScheduled(
                action:
                    'build_xml',

                nextRetryAt:
                    '2026-09-27 04:00:00',

                errorCode:
                    'AUTOMATION_BUILD_XML_FAILED',

                errorMessage:
                    'Fallo de prueba construyendo XML.'
            );

        $completed =
            $document->withUnsignedXmlBuilt(
                'private/dte/test_unsigned.xml'
            );

        $this->assertSame(
            DteStatus::XML_BUILT->value,
            $completed->status()
        );

        $this->assertRetryLimpio(
            $completed
        );

        $this->assertReferenciasDeNegocioConservadas(
            $completed
        );
    }

    public function test_build_ted_exitoso_limpia_el_retry_interno(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::XML_BUILT->value,

                unsignedXmlPath:
                    'private/dte/test_unsigned.xml'
            )
            ->withAutomationRetryScheduled(
                action:
                    'build_ted',

                nextRetryAt:
                    '2026-09-27 04:00:00',

                errorCode:
                    'AUTOMATION_BUILD_TED_FAILED',

                errorMessage:
                    'Fallo de prueba construyendo TED.'
            );

        $completed =
            $document->withTedBuilt(
                tedXml:
                    '<TED>TEST</TED>',

                unsignedXmlPath:
                    'private/dte/test_with_ted.xml'
            );

        $this->assertSame(
            DteStatus::TED_BUILT->value,
            $completed->status()
        );

        $this->assertRetryLimpio(
            $completed
        );

        $this->assertReferenciasDeNegocioConservadas(
            $completed
        );
    }

    public function test_sign_xml_exitoso_limpia_el_retry_interno(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::TED_BUILT->value,

                unsignedXmlPath:
                    'private/dte/test_with_ted.xml',

                tedXml:
                    '<TED>TEST</TED>'
            )
            ->withAutomationRetryScheduled(
                action:
                    'sign_xml',

                nextRetryAt:
                    '2026-09-27 04:00:00',

                errorCode:
                    'AUTOMATION_SIGN_XML_FAILED',

                errorMessage:
                    'Fallo de prueba firmando XML.'
            );

        $completed =
            $document->withSignedXml(
                'private/dte/test_signed.xml'
            );

        $this->assertSame(
            DteStatus::SIGNED->value,
            $completed->status()
        );

        $this->assertRetryLimpio(
            $completed
        );

        $this->assertReferenciasDeNegocioConservadas(
            $completed
        );
    }

    private function assertRetryLimpio(
        DteDocument $document
    ): void {
        $this->assertNull(
            $document->automationRetryAction()
        );

        $this->assertSame(
            0,
            $document->automationRetryCount()
        );

        $this->assertNull(
            $document->automationNextRetryAt()
        );
    }

    private function assertReferenciasDeNegocioConservadas(
        DteDocument $document
    ): void {
        $this->assertSame(
            77,
            $document->folio()
        );

        $this->assertSame(
            15,
            $document->cafId()
        );

        $this->assertSame(
            21,
            $document->folioReservationId()
        );

        $this->assertSame(
            1,
            $document->externalSystemId()
        );
    }

    private function documento(
        string $status,
        ?string $unsignedXmlPath = null,
        ?string $tedXml = null
    ): DteDocument {
        return new DteDocument(
            id:
                1,

            externalId:
                'automation-retry-completion-test',

            companyId:
                1,

            dteType:
                DteType::BOLETA_ELECTRONICA,

            issueDate:
                '2026-09-27',

            status:
                $status,

            receiver:
                new ReceiverData(
                    document:
                        '11111111-1',

                    name:
                        'Cliente prueba'
                ),

            netAmount:
                1000,

            exemptAmount:
                0,

            taxAmount:
                190,

            totalAmount:
                1190,

            items:
                [],

            folio:
                77,

            siiEnvironment:
                'cert',

            unsignedXmlPath:
                $unsignedXmlPath,

            tedXml:
                $tedXml,

            externalSystemId:
                1,

            cafId:
                15,

            folioReservationId:
                21
        );
    }
}