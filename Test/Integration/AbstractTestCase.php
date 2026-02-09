<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Test\Integration;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractTestCase extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\Framework\Registry $registry;
    protected ?\Magento\Framework\App\ObjectManager $objectManager;
    protected ?\Magento\Store\Model\StoreManager $storeManager;
    protected ?\Magento\Quote\Api\CartManagementInterface $cartManagement;
    protected ?\Magento\Quote\Api\CartRepositoryInterface $cartRepository;
    protected ?\Magento\Catalog\Model\ProductRepository $productRepository;
    protected ?\Magento\Sales\Api\OrderManagementInterface $orderManagement;
    protected ?\Magento\Sales\Model\OrderRepository $orderRepository;
    protected ?\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    protected ?\Magento\Inventory\Model\StockRepository $stockRepository;
    protected ?\Magento\Store\Model\StoreRepository $storeRepository;
    protected ?\Magento\Quote\Api\Data\CartItemInterfaceFactory $cartItemFactory;
    protected ?\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface $getSourceItemsBySkuInterface;
    protected ?\Magento\InventorySales\Model\GetAssignedSalesChannelsForStock $getAssignedSalesChannelsForStock;

    public function setUp(): void
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

        $this->getSourceItemsBySkuInterface = $this->objectManager->get(\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface::class);
        $this->getAssignedSalesChannelsForStock = $this->objectManager->create(\Magento\InventorySales\Model\GetAssignedSalesChannelsForStock::class);
    }

    protected function placeOrder(string $sku, int $quoteItemQty, \Magento\Quote\Api\Data\CartInterface $cart): int
    {
        $product = $this->productRepository->get($sku);
        $cartItem = $this->getCartItem($product, $quoteItemQty, (int)$cart->getId());
        $cart->addItem($cartItem);
        $this->cartRepository->save($cart);

        $orderId = $this->cartManagement->placeOrder($cart->getId());
        return $orderId;
    }

    protected function getCartByStockId(int $stockId): \Magento\Quote\Api\Data\CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'test_order_1')
            ->create();
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());

        $salesChannels = $this->getAssignedSalesChannelsForStock->execute($stockId);
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

    protected function getCartItem(\Magento\Catalog\Api\Data\ProductInterface $product, float $quoteItemQty, int $cartId): object
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

    protected function deleteOrderById(int $orderId): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->orderManagement->cancel($orderId);
        $this->orderRepository->delete($this->orderRepository->get($orderId));
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }
}
