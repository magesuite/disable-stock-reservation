<?php

namespace MageSuite\DisableStockReservation\Service;

class ReservationManager
{
    protected $reservationBuilder;

    protected $appendReservations;

    public function __construct(
        \Magento\InventoryReservations\Model\ReservationBuilder $reservationBuilder,
        \Magento\InventoryReservations\Model\AppendReservations $appendReservations
    )
    {
        $this->reservationBuilder = $reservationBuilder;
        $this->appendReservations = $appendReservations;
    }

    public function addCompensation(int $stockId, \Magento\InventorySales\Model\ItemToSell $item, \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent)
    {
        $metadata = $this->buildMetadata($salesEvent)->toJson();
        $reservation = $this->reservationBuilder->setStockId($stockId)
                                ->setSku($item->getSku())
                                ->setQuantity($item->getQuantity() * -1)
                                ->setMetadata($metadata)
                                ->build();

        $this->appendReservations->execute([$reservation]);
    }

    protected function buildMetadata(\Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent)
    {
        return new \Magento\Framework\DataObject(
        [
            "event_type" => $salesEvent->getType(),
            "object_type" => $salesEvent->getObjectType(),
            "object_id" => $salesEvent->getObjectId()
        ]);
    }
}