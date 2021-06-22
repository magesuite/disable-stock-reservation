<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReturnQtyToStockTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
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
}
