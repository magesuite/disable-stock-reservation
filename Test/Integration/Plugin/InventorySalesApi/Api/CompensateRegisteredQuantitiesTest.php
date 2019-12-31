<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Plugin\InventorySalesApi\Api;

class CompensateRegisteredQuantitiesTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @var \Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity
     */
    protected $getReservationQuantity;

    public function setUp()
    {
        parent::setUp();
        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);
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
    public function testReservationQuantityAfterOrder()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qty = $this->getReservationQuantity->execute($sku, $stockId);
        $this->assertEquals(0, $qty);

        $this->deleteOrderById($orderId);
    }

    public static function loadProductsFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/products.php";
    }

    public static function loadSourcesFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/sources.php";
    }

    public static function loadStocksFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stocks.php";
    }

    public static function loadStockSourceLinksFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stock_source_links.php";
    }

    public static function loadSourceItemsFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/source_items.php";
    }

    public static function loadWebsiteWithStoresFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/websites_with_stores.php";
    }

    public static function loadSourceItemsConfigurableFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-configurable-product/Test/_files/source_items_configurable.php";
    }

    public static function loadStockWebsiteSalesChannelsFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/stock_website_sales_channels.php";
    }

    public static function loadQuoteFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/quote.php";
    }

    public static function loadReindexInventoryFixture()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-indexer/Test/_files/reindex_inventory.php";
    }

    public static function loadSourcesFixtureRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/sources_rollback.php";
    }

    public static function loadStocksFixtureRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stocks_rollback.php";
    }

    public static function loadStockSourceLinksFixtureRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stock_source_links_rollback.php";
    }

    public static function loadStockWebsiteSalesChannelsFixtureRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/stock_website_sales_channels_rollback.php";
    }

    public static function loadSourceItemsConfigurableRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-configurable-product/Test/_files/source_items_configurable_rollback.php";
    }

    public static function loadWebsiteWithStoresFixtureRollback()
    {
        include __DIR__ . "/../../../_files/websites_with_stores_rollback.php";
    }

    public static function loadQuoteFixtureRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/quote_rollback.php";
    }

    public static function loadReindexInventoryFixtureRollback()
    {
        include __DIR__ . "/../../../../../../../magento/module-inventory-indexer/Test/_files/reindex_inventory.php";
    }
}