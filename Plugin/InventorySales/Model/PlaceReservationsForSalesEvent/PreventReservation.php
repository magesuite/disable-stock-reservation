<?php

namespace MageSuite\DisableStockReservation\Plugin\InventorySales\Model\PlaceReservationsForSalesEvent;

class PreventReservation
{
    public function aroundExecute(
        \Magento\InventorySales\Model\PlaceReservationsForSalesEvent $subject,
        callable $proceed,
        array $items,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent
    ) {
        return;
    }
}
