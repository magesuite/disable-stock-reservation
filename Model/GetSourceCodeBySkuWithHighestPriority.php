<?php

namespace MageSuite\DisableStockReservation\Model;

class GetSourceCodeBySkuWithHighestPriority
{
    /**
     * @var \Magento\InventoryApi\Api\GetSourceItemsBySkuInterface
     */
    protected $getSourceItemsBySku;

    /**
     * @var \Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    protected $getSourcesAssignedToStockOrderedByPriority;

    public function __construct(
        \Magento\InventoryApi\Api\GetSourceItemsBySkuInterface $getSourceItemsBySku,
        \Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    public function execute(string $sku, int $stockId)
    {
        $sourceCode = $this->defaultSourceProvider->getCode();

        try {
            $availableSources = $this->getSourceItemsBySku->execute($sku);
            $assignedSourcesToStock = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);
            foreach ($assignedSourcesToStock as $assignedSource) {
                foreach ($availableSources as $availableSource) {
                    if ($assignedSource->getSourceCode() == $availableSource->getSourceCode()) {
                        $sourceCode = $assignedSource->getSourceCode();
                        break 2;
                    }
                }
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            //Do nothing
        }

        return $sourceCode;
    }
}