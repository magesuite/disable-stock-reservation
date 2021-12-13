<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Observer;

class ReduceSaleableQuantityBundleTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @magentoDbIsolation  disabled
     * @magentoAppIsolation enabled
     *
     * @magentoDataFixture loadProductsFixture
     * @magentoDataFixture loadBundleSources
     * @magentoDataFixture loadProductBundle
     * @magentoDataFixture loadQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     */
    public function testReduceQtyAfterBundleProductOrder()
    {
        $itemsToBuy = [
            'SKU-BUNDLE-1' => ['qty' => 1, 'options_qty' => [1, 1]], // SKU-1 qty 1 | SKU-3 qty 1
            'SKU-BUNDLE-2' => ['qty' => 1, 'options_qty' => [1, 1]]  // SKU-2 qty 1 | SKU-3 qty 1
        ];

        $sku1 = 'SKU-1';
        $sku2 = 'SKU-2';
        $sku3 = 'SKU-3';

        $qtyInStockSku1 = current($this->getSourceItemsBySkuInterface->execute($sku1))->getQuantity();
        $this->assertEquals(10, $qtyInStockSku1);
        $qtyInStockSku2 = current($this->getSourceItemsBySkuInterface->execute($sku2))->getQuantity();
        $this->assertEquals(20, $qtyInStockSku2);
        $qtyInStockSku3 = current($this->getSourceItemsBySkuInterface->execute($sku3))->getQuantity();
        $this->assertEquals(30, $qtyInStockSku3);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());

        $this->placeBundleProductOrder($cart, $itemsToBuy);

        $qtyInStockSku1 = current($this->getSourceItemsBySkuInterface->execute($sku1))->getQuantity();
        $this->assertEquals(9, $qtyInStockSku1);
        $qtyInStockSku2 = current($this->getSourceItemsBySkuInterface->execute($sku2))->getQuantity();
        $this->assertEquals(19, $qtyInStockSku2);
        $qtyInStockSku3 = current($this->getSourceItemsBySkuInterface->execute($sku3))->getQuantity();
        $this->assertEquals(28, $qtyInStockSku3);
    }

    protected function placeBundleProductOrder($cart, $itemsToBuy)
    {
        /** @var \Magento\Quote\Api\CartRepositoryInterface $cartRepository */
        $cartRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Quote\Api\CartRepositoryInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        /** @var \Magento\Quote\Api\CartManagementInterface $cartManagement */
        $cartManagement = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Quote\Api\CartManagementInterface::class);

        foreach ($itemsToBuy as $sku => $qtyData) {
            $product = $productRepository->get($sku);
            $typeInstance = $product->getTypeInstance()->setStoreFilter($product->getStoreId(), $product);
            $optionCollection = $typeInstance->getOptionsCollection($product);
            $bundleOptions = [];
            $bundleOptionsQty = [];

            foreach ($optionCollection as $option) {
                /** @var $option \Magento\Bundle\Model\Option */
                $selectionsCollection = $typeInstance->getSelectionsCollection([$option->getId()], $product);
                if ($option->isMultiSelection()) {
                    $bundleOptions[$option->getId()] = array_column($selectionsCollection->toArray(), 'selection_id');
                } else {
                    $bundleOptions[$option->getId()] = $selectionsCollection->getFirstItem()->getSelectionId();
                }
                $bundleOptionsQty[$option->getId()] = 1;
            }

            $requestData = [
                'product'           => $product->getProductId(),
                'qty'               => $qtyData['qty'],
                'bundle_option'     => $bundleOptions,
                'bundle_option_qty' => $bundleOptionsQty,
            ];
            $request = new \Magento\Framework\DataObject($requestData);
            $cart->addProduct($product, $request);
        }

        $cartRepository->save($cart);
        $cartManagement->placeOrder($cart->getId());
    }

    public static function loadProductBundle()
    {
        include __DIR__ . "/../_files/bundle_product.php";
    }

    public static function loadOrderBundleProduct()
    {
        include __DIR__ . "/../../../../../magento/module-inventory-shipping/Test/_files/order_bundle_products.php";
    }

    public static function loadBundleSources()
    {
        include __DIR__ . "/../../../../../magento/module-inventory-shipping/Test/_files/source_items_for_bundle_children.php";
    }

    public static function loadQuote()
    {
        include __DIR__ . "/../../../../../magento/module-inventory-sales-api/Test/_files/quote.php";
    }
}
