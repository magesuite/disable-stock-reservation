<?php

namespace MageSuite\DisableStockReservation\Plugin\InventorySalesApi\Api;

class PreventAddingDefaultCompensation
{
    public function aroundExecute(
        \Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface $event,
        callable $proceed,
        $items,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent
    ) {
        if ($salesEvent->getType() != \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_SHIPMENT_CREATED
            && $salesEvent->getType() != \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_ORDER_CANCELED
        ) {
            return $proceed($items, $salesChannel, $salesEvent);
        }

        //Do nothing when event is created to compensate reservation after shipment
    }
}
