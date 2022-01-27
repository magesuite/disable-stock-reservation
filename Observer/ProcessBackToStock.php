<?php

namespace MageSuite\DisableStockReservation\Observer;

class ProcessBackToStock implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\InventorySales\Model\StockByWebsiteIdResolver
     */
    protected $stockByWebsiteIdResolver;

    /**
     * @var \Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority
     */
    protected $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory
     */
    protected $sourceSelectionItemFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory
     */
    protected $sourceSelectionResultFactory;

    protected $getSourceDeductionRequestFromSourceSelection;

    protected $sourceDeductionService;

    /**
     * @var \MageSuite\DisableStockReservation\Model\Sales\Order\ItemValidation
     */
    protected $itemValidation;

    public function __construct(
        \Magento\InventorySales\Model\StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        \Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority $getSourcesAssignedToStockOrderedByPriority,
        \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory,
        \MageSuite\DisableStockReservation\Model\CancelProcessor\GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection,
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionService $sourceDeductionService,
        \MageSuite\DisableStockReservation\Model\Sales\Order\ItemValidation $itemValidation
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceSelectionItemFactory = $sourceSelectionItemFactory;
        $this->sourceSelectionResultFactory = $sourceSelectionResultFactory;
        $this->getSourceDeductionRequestFromSourceSelection = $getSourceDeductionRequestFromSourceSelection;
        $this->sourceDeductionService = $sourceDeductionService;
        $this->itemValidation = $itemValidation;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        $itemsToReturn = $this->getItemsToReturn($order);
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

    protected function getItemsToReturn(\Magento\Sales\Model\Order $order)
    {
        $itemsSkus = [];
        foreach ($order->getItems() as $orderItem) {
            if (! $this->itemValidation->validate($orderItem)) {
                continue;
            }

            $itemsSkus[$orderItem->getSku()] = $orderItem->getQtyOrdered();
        }

        return $itemsSkus;
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
