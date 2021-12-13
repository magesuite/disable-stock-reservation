<?php

namespace MageSuite\DisableStockReservation\Plugin\InventorySourceSelectionApi\Model;

class CastSkusToStrings
{
    public function beforeExecute(\Magento\InventorySourceSelectionApi\Model\GetInStockSourceItemsBySkusAndSortedSource $subject, array $skus, array $sortedSourceCodes)
    {
        $skus = array_map('strval', $skus);
        return [$skus, $sortedSourceCodes];
    }
}
