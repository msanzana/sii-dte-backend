<?php
namespace App\Modules\Dte\Domain\ValueObjects;
final class LocationSummary
{
    public function __construct(
        private readonly int $cityId,
        private readonly string $cityName,
        private readonly string $comuneName,
    )
    {}

    public function cityId(): int
    {
        return $this->cityId;
    }
    public function cityName(): string
    {
        return $this->cityName;
    }
    public function comuneName(): string
    {
        return $this->comuneName;
    }
}
