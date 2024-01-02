<?php

declare(strict_types=1);

namespace MageSuite\DisableStockReservation\Service;

class GetItemsToReturn
{
    protected \MageSuite\DisableStockReservation\Model\Sales\Order\ItemValidation $itemValidation;
    protected \Magento\Framework\DB\Adapter\AdapterInterface $connection;

    public function __construct(
        \MageSuite\DisableStockReservation\Model\Sales\Order\ItemValidation $itemValidation,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->itemValidation = $itemValidation;
    }

    public function execute(\Magento\Sales\Model\Order $order): array
    {
        $itemsSkus = $this->getValidatedSkus($order);

        if (!empty($itemsSkus)) {
            $existedSkus = $this->getExistingSkusBasedOnItemsSkus($itemsSkus);
            $itemsSkus = array_intersect_key($itemsSkus, $existedSkus);
        }

        return $itemsSkus;
    }

    protected function getValidatedSkus(\Magento\Sales\Model\Order $order): array
    {
        $itemsSkus = [];

        foreach ($order->getItems() as $orderItem) {
            if (!$this->itemValidation->validate($orderItem)) {
                continue;
            }

            $itemsSkus[$orderItem->getSku()] = $orderItem->getQtyOrdered();
        }

        return $itemsSkus;
    }

    protected function getExistingSkusBasedOnItemsSkus(array $itemSkus): array
    {
        $skus = array_keys($itemSkus);
        $tableName = $this->connection->getTableName('catalog_product_entity');

        $select = $this->connection->select()->from($tableName, 'sku')
            ->where('sku in (?)', $skus);

        return array_flip($this->connection->fetchCol($select));
    }
}
