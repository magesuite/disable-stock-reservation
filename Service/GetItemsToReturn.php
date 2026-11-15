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

            $qtyToReturn = round(
                $orderItem->getQtyOrdered()
                - $this->getInvoicedOrShippedQty($orderItem)
                - (float)$orderItem->getQtyBackordered(),
                8
            );

            if ($qtyToReturn <= 0) {
                continue;
            }

            $itemsSkus[$orderItem->getSku()] = $qtyToReturn;
        }

        return $itemsSkus;
    }

    /**
     * Core leaves qty_canceled at 0 on dummy children (configurable child, fixed price bundle shipped separately,
     * dynamic price bundle shipped together), so the remaining qty is computed from invoiced, shipped and refunded
     * quantities, taken from the parent where the child is a dummy.
     */
    protected function getInvoicedOrShippedQty(\Magento\Sales\Model\Order\Item $orderItem): float
    {
        $parentItem = $orderItem->getParentItem();
        $parentRatio = $parentItem && (float)$parentItem->getQtyOrdered() > 0
            ? $orderItem->getQtyOrdered() / $parentItem->getQtyOrdered()
            : 0.0;

        $invoicedQty = (float)$orderItem->getQtyInvoiced();
        $refundedQty = (float)$orderItem->getQtyRefunded();
        if ($parentItem && $orderItem->isDummy()) {
            $invoicedQty = $parentItem->getQtyInvoiced() * $parentRatio;
            $refundedQty = $parentItem->getQtyRefunded() * $parentRatio;
        }

        $shippedQty = (float)$orderItem->getQtyShipped();
        if ($parentItem && $orderItem->isDummy(true)) {
            $shippedQty = $parentItem->getQtyShipped() * $parentRatio;
        }

        return max($invoicedQty, $shippedQty + $refundedQty);
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
