<?php

namespace MageSuite\DisableStockReservation\Observer;

class PlaceOrderObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $sourceDeductionManager;

    protected $salesEventFactory;

    protected $deductionRequestsFromSourceSelectionFactory;

    public function __construct(
        \MageSuite\DisableStockReservation\Service\SourceDeductionManager $sourceDeductionManager,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory $salesEventFactory,
        \Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory $deductionRequestsFromSourceSelectionFactory
    )
    {
        $this->sourceDeductionManager = $sourceDeductionManager;
        $this->salesEventFactory = $salesEventFactory;
        $this->deductionRequestsFromSourceSelectionFactory = $deductionRequestsFromSourceSelectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $salesEvent = $this->salesEventFactory->create([
            'type' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_ORDER_PLACED,
            'objectType' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => $order->getEntityId(),
        ]);

        $this->sourceDeductionManager->process($order, $salesEvent);
    }
}