<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\IntegrationLogEloquentModel;
use Illuminate\Support\Facades\Log;

final class EloquentIntegrationLogRepository implements IntegrationLogRepositoryInterface
{

    public function info(
        string $channel,
        string $message,
        array $context = [],
        ?int $companyId = null,
        ?int $documentId = null,
        ?string $code = null
    ): void {
        $this->write('info',$channel,$message,$context,$companyId,$documentId,$code);
    }

    public function warning(
        string $channel,
        string $message,
        array $context = [],
        ?int $companyId = null,
        ?int $documentId = null,
        ?string $code = null
    ): void {
        $this->write('warning',$channel,$message,$context,$companyId,$documentId,$code);
    }

    public function error(
        string $channel,
        string $message,
        array $context = [],
        ?int $companyId = null,
        ?int $documentId = null,
        ?string $code = null
    ): void {
        $this->write('error',$channel,$message,$context,$companyId,$documentId,$code);
    }

    public function write(
        string $level,
        string $channel,
        string $message,
        array $context,
        ?int $companyId,
        ?int $documentId,
        ?int $code
    ): void {
        Log::channel('stack')->log($level, "[{$channel}] {$message}", $context);
        IntegrationLogEloquentModel::query()->create([
            'company_id' => $companyId,
            'dte_document_id' => $documentId,
            'channel' => $channel,
            'level' => $level,
            'code' => $code,
            'message' => $code,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
