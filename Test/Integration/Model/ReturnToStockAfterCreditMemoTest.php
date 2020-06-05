<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Model;

class ReturnToStockAfterCreditMemoTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    const DEFAULT_STORE_ID = 1;

    /**
     * @var \Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity
     */
    protected $getReservationQuantity;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $creditMemoFactory;

    public function setUp()
    {
        parent::setUp();
        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);
        $this->invoiceService = $this->objectManager->get(\Magento\Sales\Model\Service\InvoiceService::class);
        $this->creditMemoService = $this->objectManager->get(\Magento\Sales\Model\Service\CreditmemoService::class);
        $this->creditMemoFactory = $this->objectManager->get(\Magento\Sales\Model\Order\CreditmemoFactory::class);
    }

    /**
     * @magentoDataFixture loadProductsFixture
     * @magentoDataFixture loadSourcesFixture
     * @magentoDataFixture loadStocksFixture
     * @magentoDataFixture loadStockSourceLinksFixture
     * @magentoDataFixture loadSourceItemsFixture
     * @magentoDataFixture loadWebsiteWithStoresFixture
     * @magentoDataFixture loadStockWebsiteSalesChannelsFixture
     * @magentoDataFixture loadQuoteFixture
     * @magentoDataFixture loadReindexInventoryFixture
     * @magentoDbIsolation disabled
     */
    public function testProductsHaveCorrectQtyAfterCreditMemoWithReturnToStockEnabled()
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
        $data['qtys'] = [$orderItem->getId() => $orderItem->getQtyOrdered()];
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