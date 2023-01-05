<?php

namespace MageSuite\DisableStockReservation\Model;

class InventoryRequestFromOrderFactory
{
    protected \Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver;

    protected \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory $itemRequestFactory;

    protected \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory $inventoryRequestFactory;

    protected \MageSuite\DisableStockReservation\Model\Sales\Order\ItemValidation $itemValidation;

    public function __construct(
        \Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory $itemRequestFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory $inventoryRequestFactory,
        \MageSuite\DisableStockReservation\Model\Sales\Order\ItemValidation $itemValidation
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->itemValidation = $itemValidation;
    }

    public function create(\Magento\Sales\Model\Order $order): \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface
    {
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        $processedItems = [];
        foreach ($order->getItems() as $orderItem) {
            if (!$this->itemValidation->validate($orderItem)) {
                continue;
            }

            $itemSku = $orderItem->getSku() ?: $orderItem->getProduct()->getSku();
            $processedItems[$itemSku] = $this->buildProcessedItem($processedItems, $itemSku, $orderItem);
        }

        return $this->inventoryRequestFactory->create([
            'stockId' => $stockId,
            'items' => $this->getItemsRequest($processedItems)
        ]);
    }

    public function buildProcessedItem($processedItems, $itemSku, $orderItem)
    {
        $processedItem = $processedItems[$itemSku] ?? [];

        if (empty($processedItem)) {
            $processedItem = [
                'sku' => $itemSku,
                'qty' => 0
            ];
        }

        $processedItem['qty'] += $orderItem->getQtyOrdered();
        return $processedItem;
    }

    protected function getItemsRequest($items): array
    {
        $requestItems = [];
        foreach ($items as $itemRequestData) {
            $requestItems[] = $this->createRequestItem($itemRequestData);
        }

        return $requestItems;
    }

    public function createRequestItem($itemRequestData)
    {
        return $this->itemRequestFactory->create($itemRequestData);
    }
}
