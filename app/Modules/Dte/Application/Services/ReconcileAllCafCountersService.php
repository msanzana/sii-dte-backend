<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use Throwable;

final class ReconcileAllCafCountersService
{
    public function __construct(
        private readonly SiiCafRepositoryInterface $cafRepository,
        private readonly RecalculateCafCountersService $recalculateService,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    ){}
    
    public function execute(int $batchSize = 200): int
    {
        $afterId = 0;
        $processed = 0;

        do {
            $cafIds = $this->cafRepository->findIdsForCounterReconciliation(
                afterId: $afterId,
                limit: $batchSize
            );

            if($cafIds === 1)
            {
                break;
            }

            foreach ($cafIds as $cafId)
            {
                try {
                    $this->recalculateService->execute($cafId);
                    $processed++;
                } catch (Throwable $exception) {
                    $this->logRepository->error(
                        channel: 'caf:counter_reconciliation',
                        message: 'No fue posible reconciliar los contadores del CAF.',
                        context: [
                            'caf_id' => $cafId,
                            'exception' => $exception->getMessage(),
                        ],
                        code: 'CAF_COUNTER_RECONCILIATION_FAILED'
                    );
                }
                $afterId = max($afterId,$cafId);
            }
        } while (count($cafIds) === $batchSize);

        return $processed;
    }
}