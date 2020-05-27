<?php

namespace MageSuite\DisableStockReservation\Test\Integration\Model;

class ReturnToStockAfterCreditMemoTest extends \MageSuite\DisableStockReservation\Test\Integration\AbstractTestCase
{
    /**
     * @var \Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity
     */
    protected $getReservationQuantity;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $creditmemoFactory;

    public function setUp()
    {
        parent::setUp();
        $this->getReservationQuantity = $this->objectManager->get(\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity::class);
        $this->invoiceService = $this->objectManager->get(\Magento\Sales\Model\Service\InvoiceService::class);
        $this->creditmemoFactory = $this->objectManager->get(\Magento\Sales\Model\Order\CreditmemoFactory::class);
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
    public function testProductsHaveCorrectQtyAfterCreditMemo()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $order = $this->orderRepository->get($orderId);
        $orderItems = $order->getItems();
        $orderItem = reset($orderItems);

        $data['qtys'] = [$orderItem->getId() => $orderItem->getQtyOrdered()];

        $invoice = $this->invoiceService->prepareInvoice($order, [$orderItem->getId() => $orderItem->getQtyOrdered()]);
        $invoice->register();
        $invoice->save();
        $order->save();

        $creditMemo = $this->creditmemoFactory->createByInvoice($invoice, $data);
        $creditMemo->save();

        $qty = $this->getReservationQuantity->execute($sku, $stockId);
        $this->assertEquals(0, $qty);
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
    public function testProductsHaveCorrectQtyAfterCreditMemoWithReturnToStockEnabled()
    {
        $sku = 'SKU-2';
        $qty = 2;
        $stockId = 30;

        $cart = $this->getCartByStockId($stockId);
        $orderId = $this->placeOrder($sku, $qty, $cart);
        $order = $this->orderRepository->get($orderId);
        $orderItems = $order->getItems();
        $orderItem = reset($orderItems);

        $data['qtys'] = [$orderItem->getId() => $orderItem->getQtyOrdered()];

        $invoice = $this->invoiceService->prepareInvoice($order, [$orderItem->getId() => $orderItem->getQtyOrdered()]);
        $invoice->register();
        $invoice->save();
        $order->save();

        $creditMemo = $this->creditmemoFactory->createByInvoice($invoice, $data);
        foreach ($creditMemo->getItems() as $creditmemoItem) {
            $creditmemoItem->setBackToStock(true);
        }
        $creditMemo->save();

        $qty = $this->getReservationQuantity->execute($sku, $stockId);
        $this->assertEquals(0, $qty);
    }
}
