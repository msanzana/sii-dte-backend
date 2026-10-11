<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\ValueObjects\DtePdf417PhysicalMetrics;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;

final class DtePdf417PhysicalMetricsService
{
    public const MIN_X_DIM_MILS = 6.7;

    public const QUIET_ZONE_INCHES = 0.25;

    public const RECOMMENDED_MAX_SYMBOL_WIDTH_MM = 90.0;

    public const RECOMMENDED_MAX_SYMBOL_HEIGHT_MM = 30.0;

    private const MIL_TO_MM = 0.0254;

    private const INCH_TO_MM = 25.4;
    public const MIN_COLUMNS = 1;
    public const MAX_COLUMNS = 30;

    /*
    |--------------------------------------------------------------------------
    | Geometría horizontal del PDF417 completo
    |--------------------------------------------------------------------------
    |
    | Cada codeword utiliza 17 módulos.
    |
    | Además de las columnas de datos existen:
    |
    | START             17 módulos
    | indicador izq.    17 módulos
    | indicador der.    17 módulos
    | STOP              18 módulos
    |
    | Total fijo: 69 módulos.
    |
    */

    private const MODULES_PER_DATA_COLUMN = 17;

    private const FIXED_HORIZONTAL_MODULES = 69;
    public function maximumColumnsForWidthMm(
        float $availableWidthMm,
        float $xDimMils = self::MIN_X_DIM_MILS
    ): int {
        if ($availableWidthMm <= 0) {
            throw new \InvalidArgumentException(
                'El ancho disponible debe ser mayor que cero.'
            );
        }

        if ($xDimMils < self::MIN_X_DIM_MILS) {
            throw new \InvalidArgumentException(
                sprintf(
                    'X Dim no puede ser inferior a %.1f mils.',
                    self::MIN_X_DIM_MILS
                )
            );
        }

        $xDimMm =
            $xDimMils
            * self::MIL_TO_MM;

        $quietZoneMm =
            self::QUIET_ZONE_INCHES
            * self::INCH_TO_MM;

        $availableSymbolWidthMm =
            $availableWidthMm
            - (2 * $quietZoneMm);

        if ($availableSymbolWidthMm <= 0) {
            return 0;
        }

        $availableModules =
            (int) floor(
                $availableSymbolWidthMm
                / $xDimMm
            );

        $availableDataModules =
            $availableModules
            - self::FIXED_HORIZONTAL_MODULES;

        if ($availableDataModules < self::MODULES_PER_DATA_COLUMN) {
            return 0;
        }

        $columns =
            (int) floor(
                $availableDataModules
                / self::MODULES_PER_DATA_COLUMN
            );

        return min(
            self::MAX_COLUMNS,
            max(
                0,
                $columns
            )
        );
    }
    public function calculate(
        DtePdf417RenderResult $result,
        float $xDimMils = self::MIN_X_DIM_MILS
    ): DtePdf417PhysicalMetrics {
        if (
            $xDimMils
            < self::MIN_X_DIM_MILS
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'X Dim no puede ser inferior a %.1f mils.',
                    self::MIN_X_DIM_MILS
                )
            );
        }

        if (
            $result->moduleAspectRatio()
            !== 3
        ) {
            throw new \DomainException(
                'El PDF417 debe mantener una relación Y:X de 3:1.'
            );
        }

        $xDimMm =
            $xDimMils
            * self::MIL_TO_MM;

        $rowHeightMm =
            $xDimMm
            * $result->moduleAspectRatio();

        $quietZoneMm =
            self::QUIET_ZONE_INCHES
            * self::INCH_TO_MM;

        $symbolWidthModules =
            (
                $result->columns()
                * self::MODULES_PER_DATA_COLUMN
            )
            + self::FIXED_HORIZONTAL_MODULES;

        $symbolHeightModules =
            $result->rows()
            * $result->moduleAspectRatio();

        $symbolWidthMm =
            $symbolWidthModules
            * $xDimMm;

        $symbolHeightMm =
            $symbolHeightModules
            * $xDimMm;

        $totalWidthMm =
            $symbolWidthMm
            + (2 * $quietZoneMm);

        $totalHeightMm =
            $symbolHeightMm
            + (2 * $quietZoneMm);

        $withinRecommendedSymbolSize =
            $symbolWidthMm
                <= self::RECOMMENDED_MAX_SYMBOL_WIDTH_MM
            &&
            $symbolHeightMm
                <= self::RECOMMENDED_MAX_SYMBOL_HEIGHT_MM;

        return new DtePdf417PhysicalMetrics(
            xDimMils: $xDimMils,
            xDimMm: $xDimMm,
            rowHeightMm: $rowHeightMm,
            quietZoneMm: $quietZoneMm,
            symbolWidthModules:
                $symbolWidthModules,
            symbolHeightModules:
                $symbolHeightModules,
            symbolWidthMm:
                $symbolWidthMm,
            symbolHeightMm:
                $symbolHeightMm,
            totalWidthMm:
                $totalWidthMm,
            totalHeightMm:
                $totalHeightMm,
            withinRecommendedSymbolSize:
                $withinRecommendedSymbolSize,
        );
    }
}
