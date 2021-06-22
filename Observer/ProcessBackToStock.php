<?php

namespace MageSuite\DisableStockReservation\Observer;

class ProcessBackToStock implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\InventorySalesApi\Model\ReturnProcessor\GetSourceDeductedOrderItemsInterface
     */
    protected $getSourceDeductedOrderItems;

    /**
     * @var \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;

    /**
     * @var \Magento\InventoryApi\Api\SourceItemsSaveInterface
     */
    protected $sourceItemsSave;

    /**
     * @var \MageSuite\DisableStockReservation\Model\GetSourceSelectionResultFromOrder
     */
    protected $getSourceSelectionResultFromOrder;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\InventorySalesApi\Model\ReturnProcessor\GetSourceDeductedOrderItemsInterface $getSourceDeductedOrderItems,
        \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemsSave,
        \MageSuite\DisableStockReservation\Model\GetSourceSelectionResultFromOrder $getSourceSelectionResultFromOrder
    ) {
        $this->productRepository = $productRepository;
        $this->getSourceDeductedOrderItems = $getSourceDeductedOrderItems;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->getSourceSelectionResultFromOrder = $getSourceSelectionResultFromOrder;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $sourceSelectionResult = $this->getSourceSelectionResultFromOrder->execute($order);

        foreach ($sourceSelectionResult->getSourceSelectionItems() as $item) {
            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($item->getSourceCode(), $item->getSku());
            $sourceItem->setQuantity($sourceItem->getQuantity() + $item->getQtyToDeduct());

            $processedSourceItems[] = $sourceItem;
        }

        $this->sourceItemsSave->execute($processedSourceItems);
    }
}
