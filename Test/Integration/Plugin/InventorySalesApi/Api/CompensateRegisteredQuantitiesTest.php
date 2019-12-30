<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Plugin\InventorySalesApi\Api;

class CompensateRegisteredQuantitiesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $storeManager;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Inventory\Model\StockRepository
     */
    private $stockRepository;

    /**
     * @var \Magento\Store\Model\StoreRepository
     */
    private $storeRepository;

    /**
     * @var \Magento\Quote\Api\Data\CartItemInterfaceFactory
     */
    private $cartItemFactory;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    private $convertOrder;

    /**
     * @var \Magento\InventoryApi\Api\GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySkuInterface;
    /**
     * @var \Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity
     */
    private $getReservationQuantity;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $writerConfig;

    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->registry = $this->objectManager->get(\Magento\Framework\Registry::class);
        $this->storeManager = $this->objectManager->get(\Magento\Store\Model\StoreManager::class);
        $this->cartManagement = $this->objectManager->get(\Magento\Quote\Api\CartManagementInterface::class);
        $this->cartRepository = $this->objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Model\ProductRepository::class);
        $this->orderManagement = $this->objectManager->get(\Magento\Sales\Api\OrderManagementInterface::class);
        $this->orderRepository = $this->objectManager->get(\Magento\Sales\Model\OrderRepository::class);

        $this->searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->stockRepository = $this->objectManager->get(\Magento\Inventory\Model\StockRepository::class);
        $this->storeRepository = $this->objectManager->get(\Magento\Store\Model\StoreRepository::class);
        $this->cartItemFactory = $this->objectManager->get(\Magento\Quote\Api\Data\CartItemInterfaceFactory::class);

        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);

        $this->getSourceItemsBySkuInterface = $this->objectManager->get(\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface::class);
        $this->writerConfig = $this->objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
    }

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

    private function placeOrder($sku, $quoteItemQty, $cart)
    {
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);

        $orderId = $this->cartManagement->placeOrder($cart->getId());
        return $orderId;
    }

    private function getCartByStockId($stockId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());

        $stock = $this->stockRepository->get($stockId);
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        $storeCode = 'store_for_';
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getType() == \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE) {
                $storeCode .= $salesChannel->getCode();
                break;
            }
        }

        $store = $this->storeRepository->get($storeCode);
        $this->storeManager->setCurrentStore($storeCode);
        $cart->setStoreId($store->getId());

        return $cart;
    }

    private function getCartItem(\Magento\Catalog\Api\Data\ProductInterface $product, float $quoteItemQty, int $cartId)
    {
        $cartItem = $this->cartItemFactory->create(
            [
                'data' => [
                    \Magento\Quote\Api\Data\CartItemInterface::KEY_SKU => $product->getSku(),
                    \Magento\Quote\Api\Data\CartItemInterface::KEY_QTY => $quoteItemQty,
                    \Magento\Quote\Api\Data\CartItemInterface::KEY_QUOTE_ID => $cartId,
                    'product_id' => $product->getId(),
                    'product' => $product
                ]
            ]
        );

        return $cartItem;
    }

    private function deleteOrderById(int $orderId)
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->orderManagement->cancel($orderId);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    public static function loadProductsFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/products.php";
    }

    public static function loadSourcesFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/sources.php";
    }

    public static function loadStocksFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stocks.php";
    }

    public static function loadStockSourceLinksFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stock_source_links.php";
    }

    public static function loadSourceItemsFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/source_items.php";
    }

    public static function loadWebsiteWithStoresFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/websites_with_stores.php";
    }

    public static function loadSourceItemsConfigurableFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-configurable-product/Test/_files/source_items_configurable.php";
    }

    public static function loadStockWebsiteSalesChannelsFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/stock_website_sales_channels.php";
    }

    public static function loadQuoteFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/quote.php";
    }

    public static function loadReindexInventoryFixture()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-indexer/Test/_files/reindex_inventory.php";
    }


    public static function loadSourcesFixtureRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/sources_rollback.php";
    }

    public static function loadStocksFixtureRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stocks_rollback.php";
    }

    public static function loadStockSourceLinksFixtureRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-api/Test/_files/stock_source_links_rollback.php";
    }

    public static function loadStockWebsiteSalesChannelsFixtureRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/stock_website_sales_channels_rollback.php";
    }

    public static function loadSourceItemsConfigurableRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-configurable-product/Test/_files/source_items_configurable_rollback.php";
    }

    public static function loadWebsiteWithStoresFixtureRollback()
    {
        require __DIR__ . "/../../../_files/websites_with_stores_rollback.php";
    }

    public static function loadQuoteFixtureRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-sales-api/Test/_files/quote_rollback.php";
    }

    public static function loadReindexInventoryFixtureRollback()
    {
        require __DIR__ . "/../../../../../../../magento/module-inventory-indexer/Test/_files/reindex_inventory.php";
    }
}