<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
use App\Modules\Dte\Domain\Entities\DteLineItem;
final class DteTotalsDomainService
{
    public function calculate(array $items): array
    {
        $net = 0.0;
        $exempt = 0.0;
        foreach ($items as $item) {
            if($item->taxExempt()){
                $exempt += $item->lineAmount();
                continue;
            }
            $net += $item->lineAmount();
        }
        $net = round($net, 2);
        $exempt = round($exempt, 2);
        $tax = round($net * 0.19, 2);
        $total = round($net + $exempt + $tax, 2);
        return [
            'net' => $net,
            'exempt' => $exempt,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
