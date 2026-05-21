<?php
namespace App\Modules\Dte\Domain\ValueObjects;
final class ReservedFolio
{
    public function  __construct(
        private readonly int $cafId,
        private readonly int $companyId,
        private readonly int $dteType,
        private readonly int $folio,
        private readonly int $cafFolioStart,
        private readonly int $cafFolioEnd
        )
    {}

    public function cafId() : int
    {
        return $this->cafId;
    }
    public function companyId() : int
    {
        return $this->companyId;
    }
    public function dteType() : int
    {
        return $this->dteType;
    }
    public function folio() : int
    {
        return $this->folio;
    }
    public function cafFolioStart() : int
    {
        return $this->cafFolioStart;
    }
    public function cafFolioEnd() : int
    {
        return $this->cafFolioEnd;
    }

}
