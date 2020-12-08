<?php

namespace MageSuite\DisableStockReservation\Plugin\InventoryShipping\Model\InventoryRequestFromOrderFactory;

class BundleChildrenSaleableQtyDeduction
{
    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactoryItemRequestInterfaceFactory
     */
    protected $itemRequestFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory
     */
    protected $inventoryRequestFactory;

    protected $requestItems = [];

    public function __construct(
        \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory $itemRequestFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory $inventoryRequestFactory
    ) {
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
    }

    public function afterCreate(\Magento\InventoryShipping\Model\InventoryRequestFromOrderFactory $subject, $result, $order)
    {
        $this->requestItems = [];
        foreach($order->getItems() as $orderItem) {
            if($orderItem->getProductType() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE && $orderItem->getProductOptions()['shipment_type'] === '0' ) {
                foreach ($orderItem->getChildrenItems() as $childrenItem) {
                    $this->processRequestItem($childrenItem);
                }
                continue;
            }
            if($orderItem->getProductType() !== \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $this->processRequestItem($orderItem);
            }
        }

        if(!empty($this->requestItems)) {
            $requestItems = $this->prepareRequestItem($this->requestItems);

            return $this->inventoryRequestFactory->create([
                'stockId' => $result->getStockId(),
                'items' => $requestItems
            ]);
        }

        return $result;
    }

    protected function prepareRequestItem()
    {
        foreach($this->requestItems as &$item)
        {
            unset($item['quote_item_id']);
        }

        return $this->requestItems;
    }

    protected function processRequestItem($item)
    {
        if(!isset($this->requestItems[$item->getSku()])) {
            $this->requestItems[$item->getSku()] = [
                'sku' => $item->getSku(),
                'qty' => $item->getQtyOrdered(),
                'quote_item_id' => [$item->getQuoteItemId()]
            ];
        }
        if (isset($this->requestItems[$item->getSku()]) && !in_array($item->getQuoteItemId(), $this->requestItems[$item->getSku()]['quote_item_id'])) {
            $this->requestItems[$item->getSku()]['qty'] += $item->getQtyOrdered();
            array_push($this->requestItems[$item->getSku()]['quote_item_id'], $item->getQuoteItemId());
        }
    }
}
