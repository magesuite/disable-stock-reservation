<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReduceSaleableQuantityTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
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
    public function testReduceQtyAfterOrderLeavingMoreQtyThanRequiredForNextOrder(): void
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(3, $qtyInStock);
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
    public function testReduceQtyAfterOrderLeavingLessQtyThanRequiredForNextOrder(): void
    {
        $sku = 'SKU-2';
        $qty = 3;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(2, $qtyInStock);
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
    public function testReduceQtyAfterOrderLeavingNoneQtyInTheStock(): void
    {
        $sku = 'SKU-2';
        $qty = 3;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(2, $qtyInStock);
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
    public function testThrowExceptionWhenTryingToOrderQtyGraterThenInStock(): void
    {
        $sku = 'SKU-2';
        $qty = 6;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->placeOrder($sku, $qty, $cart);
    }
}
