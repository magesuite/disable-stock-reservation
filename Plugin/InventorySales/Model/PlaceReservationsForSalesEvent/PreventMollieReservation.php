<?php

namespace MageSuite\DisableStockReservation\Plugin\InventorySales\Model\PlaceReservationsForSalesEvent;

class PreventMollieReservation
{
    public const MOLLIE_UNCANCEL_RESERVATION_CLASS = 'Mollie\Payment\Service\Order\Uncancel\OrderReservation';

    public function __construct(
        protected \MageSuite\PerformanceProduct\Service\StacktraceAnalyser $stacktraceAnalyser
    ) {
    }

    public function aroundExecute(
        \Magento\InventorySales\Model\PlaceReservationsForSalesEvent $subject,
        callable $proceed,
        array $items,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent
    ) {
        if ($this->stacktraceAnalyser->isInvokedBy(self::MOLLIE_UNCANCEL_RESERVATION_CLASS)) {
            return;
        }

        return $proceed($items, $salesChannel, $salesEvent);
    }
}
