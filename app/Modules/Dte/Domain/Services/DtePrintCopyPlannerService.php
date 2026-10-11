<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DtePrintConfiguration;
use App\Modules\Dte\Domain\Entities\DtePrintCopyConfiguration;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlan;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlanItem;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlanningContext;

final class DtePrintCopyPlannerService
{
    public function plan(
        DtePrintConfiguration $configuration,
        DtePrintPlanningContext $context
    ): DtePrintPlan {
        if (!$configuration->isRenderable()) {
            throw new \DomainException(
                'El tipo DTE no se encuentra habilitado para representación impresa.'
            );
        }

        $items = [];

        foreach ($configuration->activeCopies() as $copy) {
            if (!$this->shouldInclude(
                $copy,
                $context
            )) {
                continue;
            }

            $items[] = new DtePrintPlanItem(
                type: $copy->type(),
                name: $copy->name(),
                quantity: $copy->defaultQuantity(),
                legend: $copy->legend(),
            );
        }

        $plan = new DtePrintPlan(
            dteType: $configuration->dteType(),
            format: $configuration->defaultFormat(),
            items: $items,
        );

        if (!$plan->contains(PrintCopyType::ORIGINAL)) {
            throw new \DomainException(
                'La configuración imprimible del DTE debe producir un ejemplar original.'
            );
        }

        return $plan;
    }

    private function shouldInclude(
        DtePrintCopyConfiguration $copy,
        DtePrintPlanningContext $context
    ): bool {
        if ($copy->isRequired()) {
            return true;
        }

        return match ($copy->type()) {
            PrintCopyType::ORIGINAL => true,

            PrintCopyType::CLIENT =>
                $context->includeClientCopy(),

            PrintCopyType::CEDIBLE =>
                throw new \DomainException(
                    'Una copia CEDIBLE activa no puede configurarse como opcional.'
                ),
        };
    }
}