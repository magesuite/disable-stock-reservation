<?php

namespace MageSuite\DisableStockReservation\Observer;

class ReduceSaleableQuantity implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \MageSuite\DisableStockReservation\Service\SourceDeductionManager
     */
    protected $sourceDeductionManager;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory
     */
    protected $salesEventFactory;

    /**
     * @var \Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory
     */
    protected $deductionRequestsFromSourceSelectionFactory;

    public function __construct(
        \MageSuite\DisableStockReservation\Service\SourceDeductionManager $sourceDeductionManager,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory $salesEventFactory,
        \Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory $deductionRequestsFromSourceSelectionFactory
    ) {
        $this->sourceDeductionManager = $sourceDeductionManager;
        $this->salesEventFactory = $salesEventFactory;
        $this->deductionRequestsFromSourceSelectionFactory = $deductionRequestsFromSourceSelectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$this->isNewOrder($order)) {
            return;
        }

        $salesEvent = $this->salesEventFactory->create(
            [
            'type' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_ORDER_PLACED,
            'objectType' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => $order->getEntityId(),
            ]
        );

        $this->sourceDeductionManager->process($order, $salesEvent);
    }

    protected function isNewOrder(\Magento\Sales\Model\Order $order)
    {
        return $order->getOrigData('entity_id') ? false : true;
    }
}