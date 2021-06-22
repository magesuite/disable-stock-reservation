<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReduceSaleableQuantityConfigurableTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
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
     * @magentoDataFixture loadConfigurableProductQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     */
    public function testReduceQtyAfterOrderForConfigurableProduct()
    {
        $reservedOrderId = 'test_cart_with_configurable';
        $cart = $this->getCartByReservedId($reservedOrderId);
        $orderId = $this->placeOrder('configurable', 1, $cart);
        $this->assertNotNull($orderId);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute('simple_10'))->getQuantity();
        $this->assertEquals(999, $qtyInStock);

        $qtyInStock = current($this->getSourceItemsBySkuInterface->execute('simple_20'))->getQuantity();
        $this->assertEquals(1000, $qtyInStock);
    }

    protected function getCartByReservedId($reservedOrderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', $reservedOrderId)
            ->create();
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());
        $this->updateCart($cart);

        return $this->cartRepository->get($cart->getId());
    }

    protected function updateCart($cart)
    {
        $cart->setCustomerEmail('admin@example.com');
        $cart->setCustomerIsGuest(true);
        /** @var \Magento\Quote\Api\Data\AddressInterfaceFactory $address */
        $addressFactory = $this->objectManager->create(\Magento\Quote\Api\Data\AddressInterfaceFactory::class);
        $address = $addressFactory->create(
            [
                'data' => [
                    \Magento\Quote\Api\Data\AddressInterface::KEY_COUNTRY_ID => 'US',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_REGION_ID => 15,
                    \Magento\Quote\Api\Data\AddressInterface::KEY_LASTNAME => 'Doe',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_FIRSTNAME => 'John',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_STREET => 'example street',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_EMAIL => 'customer@example.com',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_CITY => 'example city',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_TELEPHONE => '000 0000',
                    \Magento\Quote\Api\Data\AddressInterface::KEY_POSTCODE => 12345
                ]
            ]
        );
        $cart->setBillingAddress($address);
        $cart->setShippingAddress($address);
        $cart->getPayment()->setMethod('checkmo');
        $cart->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $cart->getShippingAddress()->setCollectShippingRates(true);
        $cart->getShippingAddress()->collectShippingRates();
        $this->cartRepository->save($cart);
    }

    public static function loadConfigurableProductQuoteFixture()
    {
        include __DIR__ . "/../../../../../../dev/tests/integration/testsuite/Magento/ConfigurableProduct/_files/quote_with_configurable_product.php";
    }

    public static function loadConfigurableProductQuoteFixtureRollback()
    {
        include __DIR__ . "/../../../../../../dev/tests/integration/testsuite/Magento/ConfigurableProduct/_files/quote_with_configurable_product_rollback.php";
    }
}
