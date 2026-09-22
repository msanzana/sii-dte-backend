<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\DTOs\PrepareDteForResendInputDto;
use App\Modules\Dte\Application\UseCases\Document\PrepareDteForResendUseCase;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

final class PrepareDteForResendUseCaseTest extends TestCase
{
    public function test_prepara_para_reenvio_una_boleta_sent_con_ultimo_dispatch_rsc_rejected(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: '36569df0-08cd-4a87-81f6-a0ec7c37a5a5',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::SENT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            references: [],
            headerPayload: null,
            totalsPayload: null,
            rawInput: null,
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: 'app/private/dte/unsigned/boleta_39.xml',
            signedXmlPath: 'app/private/dte/signed/boleta_39_signed.xml',
            tedXml: '<TED>ANTIGUO</TED>',
            lastErrorCode: null,
            lastErrorMessage: null,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
            branchOfficeNumber: 1,
            facilityNumber: 1,
            externalBranchCode: 'TEMUCO-CENTRO',
            queuedAt: null,
            sentAt: new \DateTimeImmutable('2026-09-18 20:45:44'),
            acceptedAt: null,
            rejectedAt: null,
        );

        $dispatch = new SiiDispatch(
            id: 39,
            batchUuid: '8f164ea2-3bbc-4243-91bf-c357ff6dd89e',
            companyId: 1,
            dteDocumentId: 39,
            environment: 'cert',
            transportType: 'boleta_rest',
            status: DispatchStatus::REJECTED->value,
            trackId: '32182722',
            requestIdentifier: 'SetBoleta_39',
            requestPath: null,
            requestHeaders: null,
            requestBodyPath: 'app/private/dte/dispatch_boleta/boleta_envio.xml',
            responseHttpStatus: 200,
            responseBody: '{"estado":"RSC"}',
            uploadStatusCode: 'RSC',
            uploadStatusMessage: 'DTE Rechazado',
            retryCount: 0,
            nextRetryAt: null,
            errorMessage: 'DTE Rechazado',
            sentAt: '2026-09-18 20:45:44',
            lastPolledAt: '2026-09-19 03:52:54',
            processedAt: '2026-09-19 03:52:54',
        );

        $documentRepository = $this->createMock(
            DteDocumentRepositoryInterface::class
        );

        $dispatchRepository = $this->createMock(
            SiiDispatchRepositoryInterface::class
        );

        $logRepository = $this->createMock(
            IntegrationLogRepositoryInterface::class
        );

        $documentRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(39)
            ->willReturn($document);

        $dispatchRepository
            ->expects($this->once())
            ->method('findLatestByDocumentId')
            ->with(39)
            ->willReturn($dispatch);

        $savedDocument = null;

        $documentRepository
            ->expects($this->once())
            ->method('update')
            ->willReturnCallback(
                function (DteDocument $updated) use (&$savedDocument): DteDocument {
                    $savedDocument = $updated;

                    return $updated;
                }
            );

        $useCase = new PrepareDteForResendUseCase(
            documentRepository: $documentRepository,
            dispatchRepository: $dispatchRepository,
            logRepository: $logRepository,
        );

        $useCase->execute(
            new PrepareDteForResendInputDto(
                documentId: 39,
                headerPayloadPatch: [
                    'ind_servicio' => 3,
                ],
            )
        );

        $this->assertInstanceOf(
            DteDocument::class,
            $savedDocument
        );

        $this->assertSame(
            DteStatus::NEEDS_RESEND->value,
            $savedDocument->status()
        );

        $this->assertSame(
            ['ind_servicio' => 3],
            $savedDocument->headerPayload()
        );

        $this->assertSame(1, $savedDocument->folio());
        $this->assertSame(15, $savedDocument->cafId());
        $this->assertSame(21, $savedDocument->folioReservationId());

        $this->assertNull($savedDocument->unsignedXmlPath());
        $this->assertNull($savedDocument->signedXmlPath());
        $this->assertNull($savedDocument->tedXml());

        $this->assertSame(
            'RSC',
            $savedDocument->lastErrorCode()
        );

        $this->assertSame(
            'DTE Rechazado',
            $savedDocument->lastErrorMessage()
        );

        $this->assertNull($savedDocument->sentAt());
    }
    public function test_no_prepara_reenvio_si_el_ultimo_dispatch_no_es_rsc(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: '36569df0-08cd-4a87-81f6-a0ec7c37a5a5',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::SENT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            references: [],
            headerPayload: null,
            totalsPayload: null,
            rawInput: null,
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: 'app/private/dte/unsigned/boleta_39.xml',
            signedXmlPath: 'app/private/dte/signed/boleta_39_signed.xml',
            tedXml: '<TED>ANTIGUO</TED>',
            lastErrorCode: null,
            lastErrorMessage: null,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
            branchOfficeNumber: 1,
            facilityNumber: 1,
            externalBranchCode: 'TEMUCO-CENTRO',
            queuedAt: null,
            sentAt: new \DateTimeImmutable('2026-09-18 20:45:44'),
            acceptedAt: null,
            rejectedAt: null,
        );

        $dispatch = new SiiDispatch(
            id: 39,
            batchUuid: '8f164ea2-3bbc-4243-91bf-c357ff6dd89e',
            companyId: 1,
            dteDocumentId: 39,
            environment: 'cert',
            transportType: 'boleta_rest',
            status: DispatchStatus::REJECTED->value,
            trackId: '32182722',
            requestIdentifier: 'SetBoleta_39',
            requestPath: null,
            requestHeaders: null,
            requestBodyPath: 'app/private/dte/dispatch_boleta/boleta_envio.xml',
            responseHttpStatus: 200,
            responseBody: '{"estado":"RCT"}',

            // Aquí está la diferencia importante:
            // el dispatch está rechazado, pero NO por RSC.
            uploadStatusCode: 'RCT',

            uploadStatusMessage: 'Envío rechazado por una causa distinta de RSC',
            retryCount: 0,
            nextRetryAt: null,
            errorMessage: 'Envío rechazado por una causa distinta de RSC',
            sentAt: '2026-09-18 20:45:44',
            lastPolledAt: '2026-09-19 03:52:54',
            processedAt: '2026-09-19 03:52:54',
        );

        $documentRepository = $this->createMock(
            DteDocumentRepositoryInterface::class
        );

        $dispatchRepository = $this->createMock(
            SiiDispatchRepositoryInterface::class
        );

        $logRepository = $this->createMock(
            IntegrationLogRepositoryInterface::class
        );

        /*
        * El caso de uso debe cargar y bloquear el documento.
        */
        $documentRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(39)
            ->willReturn($document);

        /*
        * También debe consultar el último dispatch.
        */
        $dispatchRepository
            ->expects($this->once())
            ->method('findLatestByDocumentId')
            ->with(39)
            ->willReturn($dispatch);

        /*
        * Como el código NO es RSC, el documento NO debe modificarse.
        */
        $documentRepository
            ->expects($this->never())
            ->method('update');

        /*
        * Tampoco debe registrar el log de reproceso exitoso.
        */
        $logRepository
            ->expects($this->never())
            ->method('warning');

        $useCase = new PrepareDteForResendUseCase(
            documentRepository: $documentRepository,
            dispatchRepository: $dispatchRepository,
            logRepository: $logRepository,
        );

        /*
        * Esperamos que el caso de uso detenga el reproceso.
        */
        $this->expectException(
            \App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException::class
        );

        /*
        * Intentamos reprocesar exactamente igual que en el caso RSC.
        *
        * La diferencia está en el dispatch:
        * uploadStatusCode = RCT.
        */
        $useCase->execute(
            new PrepareDteForResendInputDto(
                documentId: 39,
                headerPayloadPatch: [
                    'ind_servicio' => 3,
                ],
            )
        );
    }
}