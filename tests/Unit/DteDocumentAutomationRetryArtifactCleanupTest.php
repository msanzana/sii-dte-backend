<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

final class DteDocumentAutomationRetryArtifactCleanupTest
    extends TestCase
{
    public function test_build_xml_programado_limpia_todos_los_artefactos_tecnicos(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::FOLIO_ASSIGNED->value
            );

        $updated =
            $document->withAutomationRetryScheduled(
                action:
                    'build_xml',

                nextRetryAt:
                    '2026-09-28 10:00:00',

                errorCode:
                    'AUTOMATION_BUILD_XML_FAILED',

                errorMessage:
                    'Fallo build_xml.'
            );

        $this->assertNull(
            $updated->unsignedXmlPath()
        );

        $this->assertNull(
            $updated->tedXml()
        );

        $this->assertNull(
            $updated->signedXmlPath()
        );

        $this->assertBusinessReferencesPreserved(
            $updated
        );
    }

    public function test_build_ted_programado_conserva_xml_base_y_limpia_ted_y_firma(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::XML_BUILT->value
            );

        $updated =
            $document->withAutomationRetryScheduled(
                action:
                    'build_ted',

                nextRetryAt:
                    '2026-09-28 10:00:00',

                errorCode:
                    'AUTOMATION_BUILD_TED_FAILED',

                errorMessage:
                    'Fallo build_ted.'
            );

        $this->assertSame(
            'xml/base_test.xml',
            $updated->unsignedXmlPath()
        );

        $this->assertNull(
            $updated->tedXml()
        );

        $this->assertNull(
            $updated->signedXmlPath()
        );

        $this->assertBusinessReferencesPreserved(
            $updated
        );
    }

    public function test_sign_xml_programado_conserva_xml_y_ted_y_limpia_solo_firma(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::TED_BUILT->value
            );

        $updated =
            $document->withAutomationRetryScheduled(
                action:
                    'sign_xml',

                nextRetryAt:
                    '2026-09-28 10:00:00',

                errorCode:
                    'AUTOMATION_SIGN_XML_FAILED',

                errorMessage:
                    'Fallo sign_xml.'
            );

        $this->assertSame(
            'xml/base_test.xml',
            $updated->unsignedXmlPath()
        );

        $this->assertSame(
            '<TED>VALIDO</TED>',
            $updated->tedXml()
        );

        $this->assertNull(
            $updated->signedXmlPath()
        );

        $this->assertBusinessReferencesPreserved(
            $updated
        );
    }

    public function test_build_xml_agotado_tambien_limpia_todos_los_artefactos_tecnicos(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::FOLIO_ASSIGNED->value,

                retryAction:
                    'build_xml',

                retryCount:
                    3
            );

        $updated =
            $document->withAutomationRetryExhausted(
                action:
                    'build_xml',

                errorCode:
                    'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED',

                errorMessage:
                    'Retries build_xml agotados.'
            );

        $this->assertNull(
            $updated->unsignedXmlPath()
        );

        $this->assertNull(
            $updated->tedXml()
        );

        $this->assertNull(
            $updated->signedXmlPath()
        );

        $this->assertBusinessReferencesPreserved(
            $updated
        );
    }

    public function test_build_ted_agotado_conserva_xml_base_y_limpia_ted_y_firma(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::XML_BUILT->value,

                retryAction:
                    'build_ted',

                retryCount:
                    3
            );

        $updated =
            $document->withAutomationRetryExhausted(
                action:
                    'build_ted',

                errorCode:
                    'AUTOMATION_BUILD_TED_RETRY_EXHAUSTED',

                errorMessage:
                    'Retries build_ted agotados.'
            );

        $this->assertSame(
            'xml/base_test.xml',
            $updated->unsignedXmlPath()
        );

        $this->assertNull(
            $updated->tedXml()
        );

        $this->assertNull(
            $updated->signedXmlPath()
        );

        $this->assertBusinessReferencesPreserved(
            $updated
        );
    }

    public function test_sign_xml_agotado_conserva_xml_y_ted_y_limpia_solo_firma(): void
    {
        $document =
            $this->documento(
                status:
                    DteStatus::TED_BUILT->value,

                retryAction:
                    'sign_xml',

                retryCount:
                    3
            );

        $updated =
            $document->withAutomationRetryExhausted(
                action:
                    'sign_xml',

                errorCode:
                    'AUTOMATION_SIGN_XML_RETRY_EXHAUSTED',

                errorMessage:
                    'Retries sign_xml agotados.'
            );

        $this->assertSame(
            'xml/base_test.xml',
            $updated->unsignedXmlPath()
        );

        $this->assertSame(
            '<TED>VALIDO</TED>',
            $updated->tedXml()
        );

        $this->assertNull(
            $updated->signedXmlPath()
        );

        $this->assertBusinessReferencesPreserved(
            $updated
        );
    }

    private function documento(
        string $status,
        ?string $retryAction = null,
        int $retryCount = 0
    ): DteDocument {
        return new DteDocument(
            id:
                1,

            externalId:
                'retry-artifact-cleanup-test',

            companyId:
                1,

            dteType:
                DteType::BOLETA_ELECTRONICA,

            issueDate:
                '2026-09-28',

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
                'xml/base_test.xml',

            signedXmlPath:
                'xml/signed_test.xml',

            tedXml:
                '<TED>VALIDO</TED>',

            externalSystemId:
                1,

            cafId:
                15,

            folioReservationId:
                21,

            automationRetryAction:
                $retryAction,

            automationRetryCount:
                $retryCount,

            automationNextRetryAt:
                $retryAction !== null
                    ? '2026-09-28 09:00:00'
                    : null
        );
    }

    private function assertBusinessReferencesPreserved(
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
}