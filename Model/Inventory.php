<?php

namespace MageSuite\DisableStockReservation\Model;

class Inventory
{
    /**
     * @var \Magento\InventorySales\Model\StockByWebsiteIdResolver
     */
    protected $stockByWebsiteIdResolver;

    /**
     * @var \Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface
     */
    protected $getSkuFromOrderItem;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory
     */
    protected $itemRequestFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory
     */
    protected $inventoryRequestFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestExtensionInterfaceFactory
     */
    protected $inventoryRequestExtensionFactory;

    public function __construct(
        \Magento\InventorySales\Model\StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        \Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory $itemRequestFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory $inventoryRequestFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestExtensionInterfaceFactory $inventoryRequestExtensionFactory
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->addressFactory = $addressFactory;
        $this->inventoryRequestExtensionFactory = $inventoryRequestExtensionFactory;
    }

    public function getRequestFromOrder(\Magento\Sales\Model\Order $order)
    {
        $stock = $this->stockByWebsiteIdResolver->execute(
            $order->getStore()->getWebsiteId()
        );

        $inventoryRequest = $this->inventoryRequestFactory->create(
            [
            "stockId" => $stock->getStockId(),
            "items" => $this->getItemToDeduct($order)
            ]
        );

        $address = $this->getAddressFromOrder($order);
        if ($address !== null) {
            $extensionAttributes = $this->inventoryRequestExtensionFactory->create();
            $extensionAttributes->setDestinationAddress($address);
            $inventoryRequest->setExtensionAttributes($extensionAttributes);
        }

        return $inventoryRequest;
    }

    protected function getItemToDeduct(\Magento\Sales\Model\Order $order)
    {
        $selectionRequestItems = [];
        foreach ($order->getItems() as $orderItem)
        {
            $itemSku = $this->getSkuFromOrderItem->execute($orderItem);
            $qty = $this->castQty($orderItem, $orderItem->getQtyOrdered());

            $selectionRequestItems[] = $this->itemRequestFactory->create(
                [
                'sku' => $itemSku,
                'qty' => $qty,
                ]
            );
        }

        return $selectionRequestItems;
    }

    protected function getAddressFromOrder(\Magento\Sales\Model\Order $order)
    {
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress === null) {
            return null;
        }

        return $this->addressFactory->create(
            [
                'country' => $shippingAddress->getCountryId(),
                'postcode' => $shippingAddress->getPostcode(),
                'street' => implode("\n", $shippingAddress->getStreet()),
                'region' => $shippingAddress->getRegion() ?? $shippingAddress->getRegionCode() ?? '',
                'city' => $shippingAddress->getCity()
            ]
        );
    }

    protected function castQty(\Magento\Sales\Api\Data\OrderItemInterface $item, $qty)
    {
        if ($item->getIsQtyDecimal()) {
            $qty = (float) $qty;
        } else {
            $qty = (int) $qty;
        }

        return $qty > 0 ? $qty : 0;
    }
}