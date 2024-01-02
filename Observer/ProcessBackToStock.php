<?php

namespace MageSuite\DisableStockReservation\Observer;

class ProcessBackToStock implements \Magento\Framework\Event\ObserverInterface
{
    protected \MageSuite\DisableStockReservation\Model\CancelProcessor\GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection;
    protected \MageSuite\DisableStockReservation\Service\GetItemsToReturn $getItemsToReturn;
    protected \Magento\InventorySales\Model\StockByWebsiteIdResolver $stockByWebsiteIdResolver;
    protected \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku;
    protected \Magento\InventorySourceDeductionApi\Model\SourceDeductionService $sourceDeductionService;
    protected \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory;
    protected \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory;
    protected \Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority $getSourcesAssignedToStockOrderedByPriority;

    public function __construct(
        \MageSuite\DisableStockReservation\Model\CancelProcessor\GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection,
        \MageSuite\DisableStockReservation\Service\GetItemsToReturn $getItemsToReturn,
        \Magento\InventorySales\Model\StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionService $sourceDeductionService,
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory,
        \Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority $getSourcesAssignedToStockOrderedByPriority
    ) {
        $this->getItemsToReturn = $getItemsToReturn;
        $this->getSourceDeductionRequestFromSourceSelection = $getSourceDeductionRequestFromSourceSelection;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->sourceDeductionService = $sourceDeductionService;
        $this->sourceSelectionItemFactory = $sourceSelectionItemFactory;
        $this->sourceSelectionResultFactory = $sourceSelectionResultFactory;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        $itemsToReturn = $this->getItemsToReturn->execute($order);
        $sortedSources = $this->getEnabledSourcesOrderedByPriorityByStockId($stockId);

        $sourceItemSelections = $this->getSourceSelectionItems($itemsToReturn, $sortedSources);
        $sourceSelectionResults = $this->sourceSelectionResultFactory->create(
            [
                'sourceItemSelections' => $sourceItemSelections,
                'isShippable' => true
            ]
        );

        $sourceDeductionRequests = $this->getSourceDeductionRequestFromSourceSelection->execute($order, $sourceSelectionResults);
        foreach ($sourceDeductionRequests as $sourceDeductionRequest) {
            $this->sourceDeductionService->execute($sourceDeductionRequest);
        }
    }

    /**
     * @param $itemsToReturn
     * @param $sortedSourceCodes
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSourceSelectionItems($itemsToReturn, $sortedSourceCodes)
    {
        $sourceItemSelections = [];
        foreach ($itemsToReturn as $returnItemSku => $returnQty) {
            try {
                $sourceItemSelections[] = $this->getSourceSelectionItem(
                    $sortedSourceCodes,
                    $returnItemSku,
                    $returnQty
                );
            // if product doesn't have source item or not exists, should be skipped
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) { // @codingStandardsIgnoreLine
            }
        }

        return $sourceItemSelections;
    }

    /**
     * @param $sortedSourceCodes
     * @param $returnItemSku
     * @param $returnQty
     * @return \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSourceSelectionItem(
        $sortedSourceCodes,
        $returnItemSku,
        $returnQty
    ): \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterface {

        $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute(current($sortedSourceCodes)->getSourceCode(), $returnItemSku);

        return $this->sourceSelectionItemFactory->create([
            'sourceCode' => $sourceItem->getSourceCode(),
            'sku' => $sourceItem->getSku(),
            'qtyToDeduct' => $returnQty * (-1),
            'qtyAvailable' => $sourceItem->getQuantity()
        ]);
    }

    protected function getEnabledSourcesOrderedByPriorityByStockId(int $stockId): array
    {
        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);
        $sources = array_filter($sources, function (\Magento\InventoryApi\Api\Data\SourceInterface $source) {
            return $source->isEnabled();
        });
        return $sources;
    }
}
