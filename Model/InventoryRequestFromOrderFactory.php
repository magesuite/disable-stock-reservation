<?php

namespace MageSuite\DisableStockReservation\Model;

class InventoryRequestFromOrderFactory
{
    /**
     * @var \Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface
     */
    protected $stockByWebsiteIdResolver;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory
     */
    protected $inventoryRequestFactory;


    public function __construct(
        \Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory $itemRequestFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory $inventoryRequestFactory
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
    }

    public function create(\Magento\Sales\Model\Order $order): \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface
    {
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        $processedItems = [];
        foreach ($order->getItems() as $orderItem) {
            $itemSku = $orderItem->getProduct()->getSku();
            $qtyOrdered = $orderItem->getQtyOrdered();

            if ($orderItem->isDeleted()
                || $orderItem->getHasChildren()
                || $this->isZero((float)$qtyOrdered)
                || $orderItem->getIsVirtual()
            ) {
                continue;
            }

            if (!isset($processedItems[$itemSku])) {
                $processedItems[$itemSku] = [
                    'sku' => '',
                    'qty' => 0
                ];
            }

            $processedItems[$itemSku]['sku'] = $itemSku;
            $processedItems[$itemSku]['qty'] += $qtyOrdered;
        }

        return $this->inventoryRequestFactory->create([
            'stockId' => $stockId,
            'items' => $this->getItemRequest($processedItems)
        ]);
    }

    protected function getItemRequest($items)
    {
        $requestItems = [];
        foreach ($items as $itemRequestData) {
            $requestItems[] = $this->itemRequestFactory->create($itemRequestData);
        }

        return $requestItems;
    }

    protected function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }
}
