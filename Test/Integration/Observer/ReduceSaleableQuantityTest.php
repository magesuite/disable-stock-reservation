<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReduceSaleableQuantityTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @magentoDbIsolation disabled
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
    public function testReduceQuantityAfterOrder()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = $this->getSourceItemsBySkuInterface->execute($sku)[9]->getQuantity();
        $this->assertEquals(3, $qtyInStock);

        $this->deleteOrderById($orderId);
    }

    public static function loadProductsFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/products.php";
    }

    public static function loadSourcesFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/sources.php";
    }

    public static function loadStocksFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/stocks.php";
    }

    public static function loadStockSourceLinksFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/stock_source_links.php";
    }

    public static function loadSourceItemsFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/source_items.php";
    }

    public static function loadWebsiteWithStoresFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-sales-api/Test/_files/websites_with_stores.php";
    }

    public static function loadSourceItemsConfigurableFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-configurable-product/Test/_files/source_items_configurable.php";
    }

    public static function loadStockWebsiteSalesChannelsFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-sales-api/Test/_files/stock_website_sales_channels.php";
    }

    public static function loadQuoteFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-sales-api/Test/_files/quote.php";
    }

    public static function loadReindexInventoryFixture()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-indexer/Test/_files/reindex_inventory.php";
    }

    public static function loadSourcesFixtureRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/sources_rollback.php";
    }

    public static function loadStocksFixtureRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/stocks_rollback.php";
    }

    public static function loadStockSourceLinksFixtureRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-api/Test/_files/stock_source_links_rollback.php";
    }

    public static function loadStockWebsiteSalesChannelsFixtureRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-sales-api/Test/_files/stock_website_sales_channels_rollback.php";
    }

    public static function loadSourceItemsConfigurableRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-configurable-product/Test/_files/source_items_configurable_rollback.php";
    }

    public static function loadWebsiteWithStoresFixtureRollback()
    {
        require __DIR__ . "/../_files/websites_with_stores_rollback.php";
    }

    public static function loadQuoteFixtureRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-sales-api/Test/_files/quote_rollback.php";
    }

    public static function loadReindexInventoryFixtureRollback()
    {
        require __DIR__ . "/../../../../../magento/module-inventory-indexer/Test/_files/reindex_inventory.php";
    }
}