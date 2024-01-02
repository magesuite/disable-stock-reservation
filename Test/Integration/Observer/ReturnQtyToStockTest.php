<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReturnQtyToStockTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture loadWebsiteWithStoresFixture
     * @magentoDataFixture loadProductsFixture
     * @magentoDataFixture loadSourcesFixture
     * @magentoDataFixture loadStocksFixture
     * @magentoDataFixture loadStockSourceLinksFixture
     * @magentoDataFixture loadSourceItemsFixture
     * @magentoDataFixture loadStockWebsiteSalesChannelsFixture
     * @magentoDataFixture loadQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     */
    public function testReturnQtyToStockAfterOrderCancel()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(3, $qtyInStock);

        $order = $this->orderRepository->get($orderId);
        $order->cancel();

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(5, $qtyInStock);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture loadWebsiteWithStoresFixture
     * @magentoDataFixture loadProductsFixture
     * @magentoDataFixture loadSourcesFixture
     * @magentoDataFixture loadStocksFixture
     * @magentoDataFixture loadStockSourceLinksFixture
     * @magentoDataFixture loadSourceItemsFixture
     * @magentoDataFixture loadStockWebsiteSalesChannelsFixture
     * @magentoDataFixture loadQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     */
    public function testReturnQtyToStockAfterOrderCancelWhenProductDoesntExist()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $order = $this->orderRepository->get($orderId);
        $getItemsToReturn = $this->objectManager->create(\MageSuite\DisableStockReservation\Service\GetItemsToReturn::class);

        $orderItemsSkus = $getItemsToReturn->execute($order);
        $this->assertNotEmpty($orderItemsSkus);
        $this->assertEquals(['SKU-2' => 2.0], $orderItemsSkus);

        $this->productRepository->deleteById($sku);
        $orderItemsSkus = $getItemsToReturn->execute($order);
        $this->assertEmpty($orderItemsSkus);

        try {
            $order->cancel();
        } catch (\Exception $e) {
            $this->fail(sprintf('Order cancellation failed: An exception occurred: %s', $e->getMessage()));
        }
    }

    protected function reindexStock()
    {
        /** @var \Magento\Indexer\Model\IndexerFactory $indexerFactory */
        $indexerFactory = $this->objectManager->create(\Magento\Indexer\Model\IndexerFactory::class);
        $indexer = $indexerFactory->create();
        $indexer->load('inventory');
        $indexer->reindexAll();
    }
}
