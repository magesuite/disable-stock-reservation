<?php

namespace MageSuite\DisableStockReservation\Service;

class SourceDeductionManager
{
    /**
     * @var \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface
     */
    protected $sourceDeductionService;

    /**
     * @var \MageSuite\DisableStockReservation\Model\SourceDeductionRequestFromOrderFactory
     */
    protected $getSourceDeductionRequestsFromOrder;

    /**
     * @var \MageSuite\DisableStockReservation\Model\GetItemsToDeductFromOrder
     */
    protected $getItemsToDeductFromOrder;

    /**
     * @var \Magento\InventorySales\Model\StockByWebsiteIdResolver
     */
    protected $stockByWebsiteIdResolver;

    /**
     * @var \MageSuite\DisableStockReservation\Model\GetSourceCodeBySkuWithHighestPriority
     */
    protected $getSourceCodeBySkuWithHighestPriority;

    public function __construct(
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface $sourceDeductionService,
        \MageSuite\DisableStockReservation\Model\GetSourceDeductionRequestsFromOrderFactory $getSourceDeductionRequestFromOrder,
        \MageSuite\DisableStockReservation\Model\GetItemsToDeductFromOrder $getItemsToDeductFromOrder,
        \Magento\InventorySales\Model\StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        \MageSuite\DisableStockReservation\Model\GetSourceCodeBySkuWithHighestPriority $getSourceCodeBySkuWithHighestPriority
    ) {
        $this->sourceDeductionService = $sourceDeductionService;
        $this->getSourceDeductionRequestsFromOrder = $getSourceDeductionRequestFromOrder;
        $this->getItemsToDeductFromOrder = $getItemsToDeductFromOrder;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSourceCodeBySkuWithHighestPriority = $getSourceCodeBySkuWithHighestPriority;
    }

    public function process(\Magento\Sales\Model\Order $order)
    {
        $orderItems = $this->getItemsToDeductFromOrder->execute($order);
        $groupedItems = $this->groupByOrderItemsBySources($orderItems, $order->getStore()->getWebsiteId());

        foreach ($groupedItems as $sourceCode => $items) {
            $sourceDeductionRequest = $this->getSourceDeductionRequestsFromOrder->execute($order, $sourceCode, $orderItems);
            $this->sourceDeductionService->execute($sourceDeductionRequest);
        }
    }

    protected function groupByOrderItemsBySources($orderItems, $websiteId)
    {
        $groupedItems = [];
        $stock = $this->stockByWebsiteIdResolver->execute($websiteId);
        foreach ($orderItems as $item) {
            $sourceCode = $this->getSourceCodeBySkuWithHighestPriority->execute($item->getSku(), $stock->getStockId());
            $groupedItems[$sourceCode][] = $item;
        }

        return $groupedItems;
    }
}