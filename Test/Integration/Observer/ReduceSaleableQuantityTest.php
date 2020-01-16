<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReduceSaleableQuantityTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
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
    public function testReduceQtyAfterOrderLeavingMoreQtyThanRequiredForNextOrder()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(3, $qtyInStock);

        $this->deleteOrderById($orderId);
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
    public function testReduceQtyAfterOrderLeavingLessQtyThanRequiredForNextOrder()
    {
        $sku = 'SKU-2';
        $qty = 3;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(2, $qtyInStock);

        $this->deleteOrderById($orderId);
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
    public function testReduceQtyAfterOrderLeavingNoneQtyInTheStock()
    {
        $sku = 'SKU-2';
        $qty = 3;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute($sku))->getQuantity();
        $this->assertEquals(2, $qtyInStock);

        $this->deleteOrderById($orderId);
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
    public function testThrowExceptionWhenTryingToOrderQtyGraterThenInStock()
    {
        $sku = 'SKU-2';
        $qty = 6;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->placeOrder($sku, $qty, $cart);
    }
}