<?php

namespace MageSuite\DisableStockReservation\Observer;

class ReduceSaleableQuantity implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \MageSuite\DisableStockReservation\Service\SourceDeductionManager
     */
    protected $sourceDeductionManager;

    public function __construct(\MageSuite\DisableStockReservation\Service\SourceDeductionManager $sourceDeductionManager)
    {
        $this->sourceDeductionManager = $sourceDeductionManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$this->isNewOrder($order)) {
            return;
        }

        $this->sourceDeductionManager->process($order);
    }

    protected function isNewOrder(\Magento\Sales\Model\Order $order)
    {
        return $order->getOrigData('entity_id') ? false : true;
    }
}