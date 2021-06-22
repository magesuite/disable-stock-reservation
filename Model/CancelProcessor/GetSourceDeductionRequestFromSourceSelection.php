<?php

namespace MageSuite\DisableStockReservation\Model\CancelProcessor;

class GetSourceDeductionRequestFromSourceSelection
{
    /**
     * @var \Magento\Store\Model\WebsiteRepository
     */
    protected $websiteRepository;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory
     */
    protected $salesChannelFactory;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory
     */
    protected $salesEventExtensionFactory;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory
     */
    protected $salesEventFactory;

    /**
     * @var \Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory
     */
    protected $sourceDeductionRequestFactory;

    /**
     * @var \Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory
     */
    protected $itemToDeductFactory;

    public function __construct(
        \Magento\Store\Model\WebsiteRepository $websiteRepository,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory $salesChannelFactory,
        \Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory $salesEventExtensionFactory,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory $salesEventFactory,
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory $sourceDeductionRequestFactory,
        \Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory $itemToDeductFactory
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->salesEventExtensionFactory = $salesEventExtensionFactory;
        $this->salesEventFactory = $salesEventFactory;
        $this->sourceDeductionRequestFactory = $sourceDeductionRequestFactory;
        $this->itemToDeductFactory = $itemToDeductFactory;
    }

    public function execute(\Magento\Sales\Model\Order $order, $sourceSelectionResult)
    {
        $websiteId = $order->getStore()->getWebsiteId();

        $sourceDeductionRequests = [];
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $salesChannel = $this->salesChannelFactory->create([
            'data' => [
                'type' => \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);

        /** @var \Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface */
        $salesEventExtension = $this->salesEventExtensionFactory->create([
            'data' => ['objectIncrementId' => (string)$order->getIncrementId()]
        ]);

        $salesEvent = $this->salesEventFactory->create([
            'type' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_ORDER_CANCELED,
            'objectType' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$order->getOrderId()
        ]);
        $salesEvent->setExtensionAttributes($salesEventExtension);

        foreach ($this->getItemsPerSource($sourceSelectionResult->getSourceSelectionItems()) as $sourceCode => $items) {
            /** @var \Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface[] $sourceDeductionRequests */
            $sourceDeductionRequests[] = $this->sourceDeductionRequestFactory->create([
                'sourceCode' => $sourceCode,
                'items' => $items,
                'salesChannel' => $salesChannel,
                'salesEvent' => $salesEvent
            ]);
        }

        return $sourceDeductionRequests;
    }

    protected function getItemsPerSource(array $sourceSelectionItems): array
    {
        $itemsPerSource = [];
        foreach ($sourceSelectionItems as $sourceSelectionItem) {
            if (!isset($itemsPerSource[$sourceSelectionItem->getSourceCode()])) {
                $itemsPerSource[$sourceSelectionItem->getSourceCode()] = [];
            }
            $itemsPerSource[$sourceSelectionItem->getSourceCode()][] = $this->itemToDeductFactory->create([
                'sku' => $sourceSelectionItem->getSku(),
                'qty' => $sourceSelectionItem->getQtyToDeduct(),
            ]);
        }

        return $itemsPerSource;
    }
}
