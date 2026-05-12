<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
interface IntegrationLogRepositoryInterface
{
    public function info(
        string $channel,
        string $message,
        array $context = [],
        ?int $companyId = null,
        ?int $documentId = null,
        ?string $code = null,
    ):void;
    public function warning(
        string $channel,
        string $message,
        array $context = [],
        ?int $companyId = null,
        ?int $documentId = null,
        ?string $code = null,
    ): void;
    public function error(
        string $channel,
        string $message,
        array $context = [],
        ?int $companyId = null,
        ?int $documentId = null,
        ?string $code = null,
    ):void;
}
