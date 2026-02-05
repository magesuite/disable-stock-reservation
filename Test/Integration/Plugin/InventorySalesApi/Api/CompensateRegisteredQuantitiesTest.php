<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Test\Integration\Plugin\InventorySalesApi\Api;

class CompensateRegisteredQuantitiesTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    protected \Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity $getReservationQuantity;

    public function setUp(): void
    {
        parent::setUp();
        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);
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
    public function testReservationQuantityAfterOrder(): void
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qty = $this->getReservationQuantity->execute($sku, $stockId);
        $this->assertEquals(0, $qty);
    }
}
