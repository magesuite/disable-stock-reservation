<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Plugin\InventorySalesApi\Api;

class PreventAddingDefaultCompensationTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @var \Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity
     */
    protected $getReservationQuantity;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    public function setUp()
    {
        parent::setUp();
        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);
        $this->convertOrder = $this->objectManager->get(\Magento\Sales\Model\Convert\Order::class);
    }

    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture loadProductsFixture
     * @magentoDataFixture loadSourcesFixture
     * @magentoDataFixture loadStocksFixture
     * @magentoDataFixture loadStockSourceLinksFixture
     * @magentoDataFixture loadSourceItemsFixture
     * @magentoDataFixture loadWebsiteWithStoresFixture
     * @magentoDataFixture loadStockWebsiteSalesChannelsFixture
     * @magentoDataFixture loadQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     */
    public function testReservationsAfterShipment()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $order = $this->orderRepository->get($orderId);

        $shipment = $this->convertOrder->toShipment($order);
        foreach ($order->getItems() as $item) {
            $shipmentItem = $this->convertOrder->itemToShipmentItem($item)->setQty($qty);
            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->save();

        $qty = $this->getReservationQuantity->execute($sku, $stockId);
        $this->assertEquals(0, $qty);
    }

    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture loadProductsFixture
     * @magentoDataFixture loadSourcesFixture
     * @magentoDataFixture loadStocksFixture
     * @magentoDataFixture loadStockSourceLinksFixture
     * @magentoDataFixture loadSourceItemsFixture
     * @magentoDataFixture loadWebsiteWithStoresFixture
     * @magentoDataFixture loadStockWebsiteSalesChannelsFixture
     * @magentoDataFixture loadQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     */
    public function testReservationsAfterShipmentOfAllQtyFromStock()
    {
        $sku = 'SKU-2';
        $qty = 5;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $order = $this->orderRepository->get($orderId);

        $shipment = $this->convertOrder->toShipment($order);
        foreach ($order->getItems() as $item) {
            $shipmentItem = $this->convertOrder->itemToShipmentItem($item)->setQty($qty);
            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->save();

        $qty = $this->getReservationQuantity->execute($sku, $stockId);
        $this->assertEquals(0, $qty);
    }
}