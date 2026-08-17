<?php
namespace App\Modules\Dte\Domain\Entities;
final class SiiDispatch
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $batchUuid,
        private readonly int $companyId,
        private readonly ?int $dteDocumentId,
        private readonly string $environment,
        private readonly string $transportType,
        private readonly string $status,
        private readonly ?string $trackId = null,
        private readonly ?string $requestIdentifier = null,
        private readonly ?string $requestPath = null,
        private readonly ?array $requestHeaders = null,
        private readonly ?string $requestBodyPath = null,
        private readonly ?int $responseHttpStatus = null,
        private readonly ?string $responseBody = null,
        private readonly ?string $uploadStatusCode = null,
        private readonly ?string $uploadStatusMessage = null,
        private readonly int $retryCount =0,
        private readonly ?string $nextRetryAt = null,
        private readonly ?string $errorMessage = null,
        private readonly ?string $sentAt = null,
        private readonly ?string $lastPolledAt = null,
        private readonly ?string $processedAt = null,
    )
    {}

    public function id(): ?int
    {
        return $this->id;
    }
    public function batchUuid(): string
    {
        return $this->batchUuid;
    }
    public function companyId(): int
    {
        return $this->companyId;
    }
    public function dteDocumentId(): ?int
    {
        return $this->dteDocumentId;
    }
    public function environment(): string
    {
        return $this->environment;
    }
    public function transportType(): string
    {
        return $this->transportType;
    }
    public function status(): string
    {
        return $this->status;

    }
    public function trackId(): ?string
    {
        return $this->trackId;
    }
    public function requestIdentifier(): ?string
    {
        return $this->requestIdentifier;
    }
    public function requestPath(): ?string
    {
        return $this->requestPath;
    }
    public function requestHeaders(): ?array
    {
        return $this->requestHeaders;
    }
    public function requestBodyPath(): ?string
    {
        return $this->requestBodyPath;
    }
    public function responseHttpStatus(): ?int
    {
        return $this->responseHttpStatus;
    }
    public function responseBody(): ?string
    {
        return $this->responseBody;
    }
    public function uploadStatusCode(): ?string
    {
        return $this->uploadStatusCode;
    }
    public function uploadStatusMessage(): ?string
    {
        return $this->uploadStatusMessage;
    }
    public function retryCount(): int
    {
        return $this->retryCount;
    }
    public function nextRetryAt(): ?string
    {
        return $this->nextRetryAt;
    }
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
    public function sentAt(): ?string
    {
        return $this->sentAt;
    }
    public function lastPolledAt(): ?string
    {
        return $this->lastPolledAt;
    }
    public function processedAt(): ?string
    {
        return $this->processedAt;
    }
    public function withUploadResult(
        string $status,
        ?string $trackId,
        ?int $responseHttpStatus,
        ?string $responseBody,
        ?string $uploadStatusCode,
        ?string $uploadStatusMessage,
        ?string $errorMessage = null
    ):self
    {
        return new self(
            id: $this->id,
            batchUuid: $this->batchUuid,
            companyId: $this->companyId,
            dteDocumentId: $this->dteDocumentId,
            environment: $this->environment,
            transportType: $this->transportType,
            status: $status,
            trackId: $trackId,
            requestIdentifier: $this->requestIdentifier,
            requestPath: $this->requestPath,
            requestHeaders: $this->requestHeaders,
            requestBodyPath: $this->requestBodyPath,
            responseHttpStatus: $responseHttpStatus,
            responseBody: $responseBody,
            uploadStatusCode: $uploadStatusCode,
            uploadStatusMessage: $uploadStatusMessage,
            retryCount: $this->retryCount,
            nextRetryAt: $this->nextRetryAt,
            errorMessage: $errorMessage,
            sentAt: now()->format('Y-m-d H:i:s'),
            lastPolledAt: $this->lastPolledAt,
            processedAt: $this->processedAt,
        );
    }
    public function withPollingResult(
        string $status,
        ?string $responseBody,
        ?string $uploadStatusCode,
        ?string $uploadStatusMessage,
        ?string $errorMessage = null,
        ?string $processedAt = null
    ): self {
        return new self(
            id: $this->id,
            batchUuid: $this->batchUuid,
            companyId: $this->companyId,
            dteDocumentId: $this->dteDocumentId,
            environment: $this->environment,
            transportType: $this->transportType,
            status: $status,
            trackId: $this->trackId,
            requestIdentifier: $this->requestIdentifier,
            requestPath: $this->requestPath,
            requestHeaders: $this->requestHeaders,
            requestBodyPath: $this->requestBodyPath,
            responseHttpStatus: $this->responseHttpStatus,
            responseBody: $responseBody,
            uploadStatusCode: $uploadStatusCode,
            uploadStatusMessage: $uploadStatusMessage,
            retryCount: $this->retryCount,
            nextRetryAt: $this->nextRetryAt,
            errorMessage: $errorMessage,
            sentAt: $this->sentAt,
            lastPolledAt: now()->format('Y-m-d H:i:s'),
            processedAt: $processedAt,
        );
    }
}
