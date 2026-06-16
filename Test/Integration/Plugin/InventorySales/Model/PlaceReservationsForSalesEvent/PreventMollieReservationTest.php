<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Test\Integration\Plugin\InventorySales\Model\PlaceReservationsForSalesEvent;

#[
    \Magento\TestFramework\Fixture\DbIsolation(true),
    \Magento\TestFramework\Fixture\AppArea('adminhtml')
]
class PreventMollieReservationTest extends \PHPUnit\Framework\TestCase
{
    protected const MOLLIE_RESERVATION_CLASS = 'Mollie\Payment\Service\Order\Uncancel\OrderReservation';
    protected const SKU = 'simple';

    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        if (!class_exists(self::MOLLIE_RESERVATION_CLASS)) {
            $this->markTestSkipped('Mollie module is not installed - nothing to prevent.');
        }

        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
    }

    #[
        \Magento\TestFramework\Fixture\DataFixture('Magento/Sales/_files/order.php')
    ]
    public function testReservationTriggeredByMollieIsPrevented(): void
    {
        $order = $this->getOrder('100000001');
        $orderId = (int)$order->getEntityId();
        $orderItem = current($order->getAllVisibleItems());

        $countBefore = $this->getReservationCountForOrder($orderId);

        $mollieReservation = $this->objectManager->create(self::MOLLIE_RESERVATION_CLASS);
        $mollieReservation->execute($orderItem);

        $this->assertSame(
            $countBefore,
            $this->getReservationCountForOrder($orderId),
            'A reservation triggered by Mollie must be blocked - no row may be added.'
        );
    }

    #[
        \Magento\TestFramework\Fixture\DataFixture('Magento/Sales/_files/order.php')
    ]
    public function testReservationNotTriggeredByMollieIsAllowed(): void
    {
        $order = $this->getOrder('100000001');
        $orderId = (int)$order->getEntityId();

        $countBefore = $this->getReservationCountForOrder($orderId);

        $this->placeReservationDirectly($order);

        $this->assertGreaterThan(
            $countBefore,
            $this->getReservationCountForOrder($orderId),
            'A reservation not originating from Mollie must pass through and be appended.'
        );
    }

    protected function placeReservationDirectly(\Magento\Sales\Api\Data\OrderInterface $order): void
    {
        $websiteCode = $order->getStore()->getWebsite()->getCode();

        $itemToSell = $this->objectManager->get(\Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory::class)
            ->create(['sku' => self::SKU, 'qty' => -1.0]);

        $salesChannel = $this->objectManager->get(\Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory::class)
            ->create([
                'data' => [
                    'type' => \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
                    'code' => $websiteCode,
                ],
            ]);

        $salesEvent = $this->objectManager->get(\Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory::class)
            ->create([
                'type' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_ORDER_PLACED,
                'objectType' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId' => (string)$order->getEntityId(),
            ]);

        $this->objectManager->create(\Magento\InventorySales\Model\PlaceReservationsForSalesEvent::class)
            ->execute([$itemToSell], $salesChannel, $salesEvent);
    }

    protected function getReservationCountForOrder(int $orderId): int
    {
        $connection = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
        $table = $connection->getTableName('inventory_reservation');

        $select = $connection->select()
            ->from($table, ['count' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('metadata LIKE ?', '%"object_id":"' . $orderId . '"%');

        return (int)$connection->fetchOne($select);
    }

    protected function getOrder(string $incrementId): \Magento\Sales\Api\Data\OrderInterface
    {
        $orderRepository = $this->objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();
        $orders = $orderRepository->getList($searchCriteria)->getItems();

        return reset($orders);
    }
}
