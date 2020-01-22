<?php

namespace MageSuite\DisableStockReservation\Model;

class GetItemsToDeductFromOrder
{
    /**
     * @var \Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface
     */
    protected $getSkuFromOrderItem;

    /**
     * @var \Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory
     */
    protected $itemToDeduct;

    public function __construct(
        \Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        \Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory $itemToDeduct
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemToDeduct = $itemToDeduct;
    }

    public function execute(\Magento\Sales\Model\Order $order)
    {
        $itemToDeduct = [];
        foreach ($order->getItems() as $orderItem) {
            $sku = $this->getSkuFromOrderItem->execute($orderItem);
            $qty = $this->castQty($orderItem, $orderItem->getQtyOrdered());
            $itemToDeduct[] = $this->itemToDeduct->create([
                "sku" => $sku,
                "qty" => $qty
            ]);
        }

        return $this->groupItemsBySku($itemToDeduct);
    }

    protected function groupItemsBySku(array $itemsToDeduct) : array
    {
        $processingItems = [];
        foreach ($itemsToDeduct as $item) {
            if (empty($processingItems[$item->getSku()])) {
                $processingItems[$item->getSku()] = 0;
            }

            $processingItems[$item->getSku()] += $item->getQty();
        }

        $groupedItems = [];
        foreach ($processingItems as $sku => $qty) {
            $groupedItems[] = $this->itemToDeduct->create([
                "sku" => $sku,
                "qty" => $qty
            ]);
        }

        return $groupedItems;
    }

    protected function castQty(\Magento\Sales\Model\Order\Item $item, $qty)
    {
        if ($item->getIsQtyDecimal()) {
            return (double)$qty;
        }

        return (int)$qty;
    }
}