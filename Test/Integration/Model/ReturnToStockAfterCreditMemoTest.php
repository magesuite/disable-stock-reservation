<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Test\Integration\Model;

class ReturnToStockAfterCreditMemoTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    protected const DEFAULT_STORE_ID = 1;

    protected ?\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity $getReservationQuantity;
    protected ?\Magento\Sales\Model\Service\InvoiceService $invoiceService;
    protected ?\Magento\Sales\Model\Service\CreditmemoService $creditMemoService;
    protected ?\Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);
        $this->invoiceService = $this->objectManager->get(\Magento\Sales\Model\Service\InvoiceService::class);
        $this->creditMemoService = $this->objectManager->get(\Magento\Sales\Model\Service\CreditmemoService::class);
        $this->creditMemoFactory = $this->objectManager->get(\Magento\Sales\Model\Order\CreditmemoFactory::class);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDataFixture MageSuite_DisableStockReservation::Test/Integration/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/quote.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testProductsHaveCorrectQtyAfterCreditMemoWithReturnToStockEnabled(): void
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $this->storeManager->setCurrentStore('default');
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $order = $this->orderRepository->get($orderId);
        $orderItems = $order->getItems();
        $orderItem = reset($orderItems);

        $data = ['qtys' => [$orderItem->getId() => $orderItem->getQtyOrdered()]];
        $this->storeManager->setCurrentStore('default');
        $invoice = $this->invoiceService->prepareInvoice($order, [$orderItem->getId() => $orderItem->getQtyOrdered()]);
        $invoice->setStoreId(self::DEFAULT_STORE_ID);
        $invoice->register();

        $order = $invoice->getOrder();
        $order->setIsInProcess(true);
        $order->setStoreId(self::DEFAULT_STORE_ID);
        $order->save();
        $creditMemo = $this->creditMemoFactory->createByInvoice($invoice, $data);

        foreach ($creditMemo->getItems() as $creditMemoItem) {
            $creditMemoItem->setBackToStock(true);
        }

        $creditMemo->setStoreId(self::DEFAULT_STORE_ID);
        $this->creditMemoService->refund($creditMemo);
        $qty = $this->getReservationQuantity->execute($sku, $stockId);

        $this->assertEquals(0, $qty);
    }
}
