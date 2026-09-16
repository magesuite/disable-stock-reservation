<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReturnBackorderedQtyToStockTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture MageSuite_DisableStockReservation::Test/Integration/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testFullyBackorderedItemDoesNotIncreaseStockOnCancel(): void
    {
        $sku = 'SKU-6';
        $qty = 2;
        $stockId = 10;
        $sourceCode = 'eu-1';

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $this->assertEquals(
            0,
            $this->getSourceQty($sku, $sourceCode),
            'Nothing may be deducted from the source when the whole quantity is backordered.'
        );

        $order = $this->orderRepository->get($orderId);
        $this->assertEquals($qty, (float)$this->getFirstItem($order)->getQtyBackordered());

        $order->cancel();

        $this->assertEquals(
            0,
            $this->getSourceQty($sku, $sourceCode),
            'Returning stock for a fully backordered item must not create quantity that was never deducted.'
        );
    }

    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture MageSuite_DisableStockReservation::Test/Integration/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testPartiallyBackorderedItemReturnsOnlyDeductedQty(): void
    {
        $sku = 'SKU-2';
        $qty = 8;
        $stockId = 30;
        $sourceCode = 'us-1';

        $this->allowBackorders($sku);

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $this->assertEquals(0, $this->getSourceQty($sku, $sourceCode), 'Only the available 5 may be deducted.');

        $order = $this->orderRepository->get($orderId);
        $this->assertEquals(3, (float)$this->getFirstItem($order)->getQtyBackordered());

        $order->cancel();

        $this->assertEquals(
            5,
            $this->getSourceQty($sku, $sourceCode),
            'Only the deducted quantity may come back, not the backordered part.'
        );
    }

    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture MageSuite_DisableStockReservation::Test/Integration/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testFullyBackorderedItemIsNotReturnedAtAll(): void
    {
        $sku = 'SKU-6';
        $qty = 2;
        $stockId = 10;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);

        $getItemsToReturn = $this->objectManager->create(\MageSuite\DisableStockReservation\Service\GetItemsToReturn::class);

        $this->assertEmpty(
            $getItemsToReturn->execute($this->orderRepository->get($orderId)),
            'An item that was never deducted must not appear among the items to return.'
        );
    }

    protected function allowBackorders(string $sku): void
    {
        $stockRegistry = $this->objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
        $stockItemRepository = $this->objectManager->get(\Magento\CatalogInventory\Api\StockItemRepositoryInterface::class);

        $stockItem = $stockRegistry->getStockItemBySku($sku);
        $stockItem->setUseConfigBackorders(false);
        $stockItem->setBackorders(\Magento\CatalogInventory\Model\Stock::BACKORDERS_YES_NONOTIFY);
        $stockItem->setUseConfigMinQty(false);
        $stockItem->setMinQty(-10);
        $stockItemRepository->save($stockItem);
    }

    protected function getSourceQty(string $sku, string $sourceCode): float
    {
        foreach ($this->getSourceItemsBySkuInterface->execute($sku) as $sourceItem) {
            if ($sourceItem->getSourceCode() === $sourceCode) {
                return (float)$sourceItem->getQuantity();
            }
        }

        throw new \RuntimeException(sprintf('No source item for %s on %s.', $sku, $sourceCode));
    }

    protected function getFirstItem(\Magento\Sales\Api\Data\OrderInterface $order): \Magento\Sales\Api\Data\OrderItemInterface
    {
        return current($order->getItems());
    }
}
