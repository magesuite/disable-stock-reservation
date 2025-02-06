<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Plugin\Magento\InventoryShippingAdminUi\Model\IsOrderSourceManageable;

class SkipSourceSelection
{
    public function afterExecute(\Magento\InventoryShippingAdminUi\Model\IsOrderSourceManageable $subject, $result)
    {
        return false;
    }
}
