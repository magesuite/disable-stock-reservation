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

    public function __construct(
        \Magento\InventorySales\Model\StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        \Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority $getSourcesAssignedToStockOrderedByPriority,
        \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory,
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory,

        \MageSuite\DisableStockReservation\Model\CancelProcessor\GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection,
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionService $sourceDeductionService
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceSelectionItemFactory = $sourceSelectionItemFactory;
        $this->sourceSelectionResultFactory = $sourceSelectionResultFactory;

        $this->getSourceDeductionRequestFromSourceSelection = $getSourceDeductionRequestFromSourceSelection;
        $this->sourceDeductionService = $sourceDeductionService;
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
            if ($orderItem->isDeleted()
                || $orderItem->getHasChildren()
                || $this->isZero((float)$orderItem->getQtyOrdered())
                || $orderItem->getIsVirtual()
            ) {
                continue;
            }

            $itemsSkus[$orderItem->getSku()] = $orderItem->getQtyOrdered();
        }

        return $itemsSkus;
    }

    protected function getSourceSelectionItems($itemsToReturn, $sortedSourceCodes)
    {
        $sourceItemSelections = [];
        foreach ($itemsToReturn as $returnItemSku => $returnQty) {
            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute(current($sortedSourceCodes)->getSourceCode(), $returnItemSku);
            $sourceItemSelections[] = $this->sourceSelectionItemFactory->create([
                'sourceCode' => $sourceItem->getSourceCode(),
                'sku' => $sourceItem->getSku(),
                'qtyToDeduct' => $returnQty * (-1),
                'qtyAvailable' => $sourceItem->getQuantity()
            ]);
        }

        return $sourceItemSelections;
    }

    protected function getEnabledSourcesOrderedByPriorityByStockId(int $stockId): array
    {
        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);
        $sources = array_filter($sources, function (\Magento\InventoryApi\Api\Data\SourceInterface $source) {
            return $source->isEnabled();
        });
        return $sources;
    }

    protected function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }
}
