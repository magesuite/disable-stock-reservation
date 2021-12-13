<?php

namespace MageSuite\DisableStockReservation\Model\Sales\Order;

class ItemValidation
{
    public function validate($orderItem)
    {
        if ($orderItem->isDeleted()) {
            return false;
        }

        if ($orderItem->getHasChildren()) {
            return false;
        }

        if ($this->isZero((float)$orderItem->getQtyOrdered())) {
            return false;
        }

        if ($orderItem->getIsVirtual()) {
            return false;
        }

        return true;
    }

    protected function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }
}
