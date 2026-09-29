<?php

namespace App\Modules\Dte\Domain\Entities;

use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use DateTimeImmutable;

final class DteDocument
{
    /**
     * @param DteLineItem[] $items
     * @param DteReference[] $references
     */
    public function __construct(
        private readonly ?int $id,
        private readonly string $externalId,
        private readonly int $companyId,
        private readonly DteType $dteType,
        private readonly string $issueDate,
        private readonly string $status,
        private readonly ReceiverData $receiver,
        private readonly float $netAmount,
        private readonly float $exemptAmount,
        private readonly float $taxAmount,
        private readonly float $totalAmount,
        private readonly array $items,
        private readonly array $references = [],
        private readonly ?array $headerPayload = null,
        private readonly ?array $totalsPayload = null,
        private readonly ?array $rawInput = null,
        private readonly ?int $folio = null,
        private readonly ?string $siiEnvironment = null,
        private readonly ?string $unsignedXmlPath = null,
        private readonly ?string $signedXmlPath = null,
        private readonly ?string $tedXml = null,
        private readonly ?string $lastErrorCode = null,
        private readonly ?string $lastErrorMessage = null,
        private readonly ?int $externalSystemId = null,
        private readonly ?int $cafId = null,
        private readonly ?int $folioReservationId = null,
        private readonly ?int $branchOfficeNumber = null,
        private readonly ?int $facilityNumber = null,
        private readonly ?string $externalBranchCode = null,
        private readonly ?DateTimeImmutable $queuedAt = null,
        private readonly ?DateTimeImmutable $sentAt = null,
        private readonly ?DateTimeImmutable $acceptedAt = null,
        private readonly ?DateTimeImmutable $rejectedAt = null,
        // 🟩 NUEVO
        //
        // Acción concreta que deberá volver a ejecutar la automatización.
        // Ejemplos:
        // - build_xml
        // - build_ted
        // - sign_xml
        //
        private readonly ?string $automationRetryAction = null,

        // 🟩 NUEVO
        //
        // Cantidad de retries de automatización ya programados.
        //
        private readonly int $automationRetryCount = 0,

        // 🟩 NUEVO
        //
        // Momento desde el cual el documento vuelve a ser elegible
        // para la automatización.
        //
        private readonly ?string $automationNextRetryAt = null,

    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function companyId(): int
    {
        return $this->companyId;
    }

    public function dteType(): DteType
    {
        return $this->dteType;
    }

    public function issueDate(): string
    {
        return $this->issueDate;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function receiver(): ReceiverData
    {
        return $this->receiver;
    }

    public function netAmount(): float
    {
        return $this->netAmount;
    }

    public function exemptAmount(): float
    {
        return $this->exemptAmount;
    }

    public function taxAmount(): float
    {
        return $this->taxAmount;
    }

    public function totalAmount(): float
    {
        return $this->totalAmount;
    }

    /**
     * @return DteLineItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return DteReference[]
     */
    public function references(): array
    {
        return $this->references;
    }

    public function headerPayload(): ?array
    {
        return $this->headerPayload;
    }

    public function totalsPayload(): ?array
    {
        return $this->totalsPayload;
    }

    public function rawInput(): ?array
    {
        return $this->rawInput;
    }

    public function folio(): ?int
    {
        return $this->folio;
    }

    public function siiEnvironment(): ?string
    {
        return $this->siiEnvironment;
    }

    public function unsignedXmlPath(): ?string
    {
        return $this->unsignedXmlPath;
    }

    public function signedXmlPath(): ?string
    {
        return $this->signedXmlPath;
    }

    public function tedXml(): ?string
    {
        return $this->tedXml;
    }

    public function lastErrorCode(): ?string
    {
        return $this->lastErrorCode;
    }

    public function lastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }
    // 🟩 NUEVO

    public function automationRetryAction(): ?string
    {
        return $this->automationRetryAction;
    }

    public function automationRetryCount(): int
    {
        return $this->automationRetryCount;
    }

    public function automationNextRetryAt(): ?string
    {
        return $this->automationNextRetryAt;
    }
    public function queuedAt(): ?DateTimeImmutable
    {
        return $this->queuedAt;
    }

    public function sentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function rejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }
    public function withNeedsResend(
    array $headerPayloadPatch,
    ?string $code = null,
    ?string $message = null
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::NEEDS_RESEND->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: array_replace(
                $this->headerPayload ?? [],
                $headerPayloadPatch
            ),
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,

            /*
            * Cualquier cambio semántico invalida todos los
            * artefactos XML derivados del ciclo anterior.
            */
            unsignedXmlPath: null,
            signedXmlPath: null,
            tedXml: null,

            /*
            * Conservamos la causa que originó el reproceso.
            */
            lastErrorCode: $code,
            lastErrorMessage: $message,

            /*
            * Se conservan las asociaciones tributarias.
            */
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,

            /*
            * Comienza un nuevo ciclo operativo.
            * La historia anterior permanece en sii_dispatches.
            */
            queuedAt: null,
            sentAt: null,
            acceptedAt: null,
            rejectedAt: null,
        );
    }
    public function withFolioAndStatus(
        int $folio,
        string $status,
        ?string $siiEnvironment = null
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: $status,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $folio,
            siiEnvironment: $siiEnvironment ?? $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $this->lastErrorCode,
            lastErrorMessage: $this->lastErrorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,
        );
    }

    public function withUnsignedXmlBuilt(string $unsignedXmlPath): self
    {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::XML_BUILT->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $this->lastErrorCode,
            lastErrorMessage: $this->lastErrorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,
        );
    }

    public function withTedBuilt(
        string $tedXml,
        string $unsignedXmlPath
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::TED_BUILT->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $tedXml,
            lastErrorCode: $this->lastErrorCode,
            lastErrorMessage: $this->lastErrorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,
        );
    }

    public function withSignedXml(string $signedXmlPath): self
    {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::SIGNED->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $this->lastErrorCode,
            lastErrorMessage: $this->lastErrorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
        );
    }
    public function withSendingStatus(): self
    {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::SENDING->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $this->lastErrorCode,
            lastErrorMessage: $this->lastErrorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,
        );
    }
    public function withSentStatus(
            ?string $code = null,
            ?string $message = null
    ): self
    {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::SENT->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $code,
            lastErrorMessage: $message,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt ?? new DateTimeImmutable(),
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,
        );
    }
    public function withAcceptedStatus(): self
    {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::ACCEPTED->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: null,
            lastErrorMessage: null,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt ?? new DateTimeImmutable(),
            rejectedAt: $this->rejectedAt,
        );
    }

    public function withAcceptedWithReparosStatus(
        ?string $code = null,
        ?string $message = null
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::ACCEPTED_WITH_REPAROS->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $code,
            lastErrorMessage: $message,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt ?? new DateTimeImmutable(),
            rejectedAt: $this->rejectedAt,
        );
    }

    public function withRejectedStatus(
    ?string $code = null,
    ?string $message = null
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: DteStatus::REJECTED->value,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath: $this->unsignedXmlPath,
            signedXmlPath: $this->signedXmlPath,
            tedXml: $this->tedXml,
            lastErrorCode: $code,
            lastErrorMessage: $message,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt ?? new DateTimeImmutable(),
        );
    }
        // 🟩 NUEVO

    public function withAutomationRetryScheduled(
        string $action,
        string $nextRetryAt,
        string $errorCode,
        string $errorMessage
    ): self {
        return new self(
            id:
                $this->id,

            externalId:
                $this->externalId,

            companyId:
                $this->companyId,

            dteType:
                $this->dteType,

            issueDate:
                $this->issueDate,

            /*
            |--------------------------------------------------------------------------
            | Estado funcional
            |--------------------------------------------------------------------------
            |
            | NO cambiamos el estado.
            |
            | Si build_xml falló en FOLIO_ASSIGNED,
            | el documento continúa FOLIO_ASSIGNED.
            |
            | Si build_ted falló en XML_BUILT,
            | continúa XML_BUILT.
            |
            | Si sign_xml falló en TED_BUILT,
            | continúa TED_BUILT.
            |
            */

            status:
                $this->status,

            receiver:
                $this->receiver,

            netAmount:
                $this->netAmount,

            exemptAmount:
                $this->exemptAmount,

            taxAmount:
                $this->taxAmount,

            totalAmount:
                $this->totalAmount,

            items:
                $this->items,

            references:
                $this->references,

            headerPayload:
                $this->headerPayload,

            totalsPayload:
                $this->totalsPayload,

            rawInput:
                $this->rawInput,

            /*
            |--------------------------------------------------------------------------
            | Folio y ambiente
            |--------------------------------------------------------------------------
            |
            | CRÍTICO:
            | se conserva el mismo folio.
            |
            */

            folio:
                $this->folio,

            siiEnvironment:
                $this->siiEnvironment,

            /*
            |--------------------------------------------------------------------------
            | Artefactos técnicos existentes
            |--------------------------------------------------------------------------
            |
            | Por ahora no borramos nada aquí.
            |
            | Cada acción de reproceso decidirá posteriormente cuáles
            | artefactos deben invalidarse.
            |
            */

            unsignedXmlPath:
                match ($action) {
                    'build_xml' =>
                        null,

                    default =>
                        $this->unsignedXmlPath,
                },

            signedXmlPath:
                match ($action) {
                    'build_xml',
                    'build_ted',
                    'sign_xml' =>
                        null,

                    default =>
                        $this->signedXmlPath,
                },

            tedXml:
                match ($action) {
                    'build_xml',
                    'build_ted' =>
                        null,

                    default =>
                        $this->tedXml,
                },

            /*
            |--------------------------------------------------------------------------
            | Diagnóstico del último error
            |--------------------------------------------------------------------------
            */

            lastErrorCode:
                $errorCode,

            lastErrorMessage:
                $errorMessage,

            /*
            |--------------------------------------------------------------------------
            | Referencias de negocio
            |--------------------------------------------------------------------------
            |
            | Estas referencias NO deben cambiar por un fallo técnico.
            |
            */

            externalSystemId:
                $this->externalSystemId,

            cafId:
                $this->cafId,

            folioReservationId:
                $this->folioReservationId,

            branchOfficeNumber:
                $this->branchOfficeNumber,

            facilityNumber:
                $this->facilityNumber,

            externalBranchCode:
                $this->externalBranchCode,

            /*
            |--------------------------------------------------------------------------
            | Retry de automatización
            |--------------------------------------------------------------------------
            */

            automationRetryAction:
                $action,

            automationRetryCount:
                $this->automationRetryCount + 1,

            automationNextRetryAt:
                $nextRetryAt,
        );
    }
    public function withAutomationRetryExhausted(
        string $action,
        string $errorCode,
        string $errorMessage
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: $this->status,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath:
                match ($action) {
                    'build_xml' =>
                        null,

                    default =>
                        $this->unsignedXmlPath,
                },

            signedXmlPath:
                match ($action) {
                    'build_xml',
                    'build_ted',
                    'sign_xml' =>
                        null,

                    default =>
                        $this->signedXmlPath,
                },

            tedXml:
                match ($action) {
                    'build_xml',
                    'build_ted' =>
                        null,

                    default =>
                        $this->tedXml,
                },
            lastErrorCode: $errorCode,
            lastErrorMessage: $errorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,

            /*
            |--------------------------------------------------------------------------
            | Retry interno agotado
            |--------------------------------------------------------------------------
            |
            | Conservamos la acción y el contador alcanzado, pero eliminamos
            | next_retry_at para impedir que el pump vuelva a seleccionarlo.
            |
            */

            automationRetryAction: $action,
            automationRetryCount: $this->automationRetryCount,
            automationNextRetryAt: null,
        );
    }

    public function withAutomationRetryNotRetryable(
        string $action,
        string $errorCode,
        string $errorMessage
    ): self {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            companyId: $this->companyId,
            dteType: $this->dteType,
            issueDate: $this->issueDate,
            status: $this->status,
            receiver: $this->receiver,
            netAmount: $this->netAmount,
            exemptAmount: $this->exemptAmount,
            taxAmount: $this->taxAmount,
            totalAmount: $this->totalAmount,
            items: $this->items,
            references: $this->references,
            headerPayload: $this->headerPayload,
            totalsPayload: $this->totalsPayload,
            rawInput: $this->rawInput,
            folio: $this->folio,
            siiEnvironment: $this->siiEnvironment,
            unsignedXmlPath:
                match ($action) {
                    'build_xml' =>
                        null,

                    default =>
                        $this->unsignedXmlPath,
                },

            signedXmlPath:
                match ($action) {
                    'build_xml',
                    'build_ted',
                    'sign_xml' =>
                        null,

                    default =>
                        $this->signedXmlPath,
                },

            tedXml:
                match ($action) {
                    'build_xml',
                    'build_ted' =>
                        null,

                    default =>
                        $this->tedXml,
                },
            lastErrorCode: $errorCode,
            lastErrorMessage: $errorMessage,
            externalSystemId: $this->externalSystemId,
            cafId: $this->cafId,
            folioReservationId: $this->folioReservationId,
            branchOfficeNumber: $this->branchOfficeNumber,
            facilityNumber: $this->facilityNumber,
            externalBranchCode: $this->externalBranchCode,
            queuedAt: $this->queuedAt,
            sentAt: $this->sentAt,
            acceptedAt: $this->acceptedAt,
            rejectedAt: $this->rejectedAt,

            /*
            |--------------------------------------------------------------------------
            | Retry interno agotado
            |--------------------------------------------------------------------------
            |
            | Conservamos la acción y el contador alcanzado, pero eliminamos
            | next_retry_at para impedir que el pump vuelva a seleccionarlo.
            |
            */

            automationRetryAction: $action,
            automationRetryCount: $this->automationRetryCount,
            automationNextRetryAt: null,
        );
    }

    public function externalSystemId(): ?int
    {
        return $this->externalSystemId;
    }

    public function cafId(): ?int
    {
        return $this->cafId;
    }

    public function folioReservationId(): ?int
    {
        return $this->folioReservationId;
    }

    public function branchOfficeNumber(): ?int
    {
        return $this->branchOfficeNumber;
    }

    public function facilityNumber(): ?int
    {
        return $this->facilityNumber;
    }

    public function externalBranchCode(): ?string
    {
        return $this->externalBranchCode;
    }
}
