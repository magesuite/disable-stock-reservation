<?php

namespace MageSuite\DisableStockReservation\Plugin\InventorySalesApi\Api;

class CompensateRegisteredQuantities
{
    protected $reservationManager;

    protected $getStockBySalesChannel;

    public function __construct(
        \MageSuite\DisableStockReservation\Service\ReservationManager $reservationManager,
        \Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface $getStockBySalesChannel)
    {
        $this->reservationManager = $reservationManager;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
    }

    public function afterExecute(
        \Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface $event,
        $result,
        $items,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent)
    {
        if($salesEvent->getType() == \MageSuite\DisableStockReservation\Model\SalesEvent::TYPE_ORDER_PLACED)
        {
            $stockId = $this->getStockBySalesChannel->execute($salesChannel)->getStockId();
            foreach ($items as $item)
            {
                $this->reservationManager->addCompensation($stockId, $item, $salesEvent);
            }
        }
    }
}