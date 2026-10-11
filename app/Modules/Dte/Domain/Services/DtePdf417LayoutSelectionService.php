<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;
use App\Modules\Dte\Domain\RendererContracts\DtePdf417RendererInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417LayoutSelection;

final class DtePdf417LayoutSelectionService
{
    public function __construct(
        private readonly DtePdf417RendererInterface $renderer,
        private readonly DtePdf417PhysicalMetricsService $physicalMetricsService,
    ) {}

    public function select(
        string $tedXmlUtf8,
        float $availableWidthMm,
        int $preferredColumns = 15,
        float $xDimMils = DtePdf417PhysicalMetricsService::MIN_X_DIM_MILS,
    ): DtePdf417LayoutSelection {
        if (
            $preferredColumns
            < DtePdf417PhysicalMetricsService::MIN_COLUMNS
            || $preferredColumns
            > DtePdf417PhysicalMetricsService::MAX_COLUMNS
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La cantidad preferida de columnas debe estar entre %d y %d.',
                    DtePdf417PhysicalMetricsService::MIN_COLUMNS,
                    DtePdf417PhysicalMetricsService::MAX_COLUMNS
                )
            );
        }

        $maximumColumns =
            $this->physicalMetricsService
                ->maximumColumnsForWidthMm(
                    $availableWidthMm,
                    $xDimMils
                );

        if (
            $maximumColumns
            < DtePdf417PhysicalMetricsService::MIN_COLUMNS
        ) {
            throw new \DomainException(
                sprintf(
                    'El ancho disponible de %.2f mm no permite alojar un PDF417 respetando X Dim y quiet zone.',
                    $availableWidthMm
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Selección inicial
        |--------------------------------------------------------------------------
        |
        | Si la preferencia cabe, se conserva.
        |
        | Si no cabe, usamos la máxima cantidad de columnas que permite
        | físicamente el ancho disponible.
        |
        | Elegir menos columnas que ese máximo sólo aumentaría las filas.
        |
        */

        $initialColumns =
            min(
                $preferredColumns,
                $maximumColumns
            );

        /*
        |--------------------------------------------------------------------------
        | Si la preferencia produce demasiadas filas
        |--------------------------------------------------------------------------
        |
        | Cuando existe ancho adicional podemos probar progresivamente más
        | columnas. Más columnas reducen el número de filas.
        |
        | Conservamos la primera excepción para no ocultar el diagnóstico real
        | si ningún candidato resulta válido.
        |
        */

        $firstException = null;

        for (
            $columns = $initialColumns;
            $columns <= $maximumColumns;
            $columns++
        ) {
            try {
                $renderResult =
                    $this->renderer->render(
                        $tedXmlUtf8,
                        $columns
                    );

                $physicalMetrics =
                    $this->physicalMetricsService
                        ->calculate(
                            $renderResult,
                            $xDimMils
                        );

                if (
                    !$physicalMetrics
                        ->fitsWithinWidthMm(
                            $availableWidthMm
                        )
                ) {
                    continue;
                }

                return new DtePdf417LayoutSelection(
                    renderResult: $renderResult,
                    physicalMetrics: $physicalMetrics,
                    availableWidthMm: $availableWidthMm,
                    preferredColumns: $preferredColumns,
                );
            } catch (InvalidTedDataException $exception) {
                $firstException ??=
                    $exception;
            }
        }

        if ($firstException !== null) {
            throw $firstException;
        }

        throw new \DomainException(
            sprintf(
                'No fue posible encontrar una geometría PDF417 válida para un ancho disponible de %.2f mm.',
                $availableWidthMm
            )
        );
    }
}