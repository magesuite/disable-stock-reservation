<?php

namespace MageSuite\DisableStockReservation\Service;

class SourceDeductionManager
{
    /**
     * @var \MageSuite\DisableStockReservation\Model\Inventory
     */
    protected $inventory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Model\SourceSelectionService
     */
    protected $sourceSelectionService;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    protected $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var \Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory
     */
    protected $sourceDeductionRequestsFromSourceSelectionFactory;

    /**
     * @var \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface
     */
    protected $sourceDeductionService;

    public function __construct(
        \MageSuite\DisableStockReservation\Model\Inventory $inventory,
        \Magento\InventorySourceSelectionApi\Model\SourceSelectionService $sourceSelectionService,
        \Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        \Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory $sourceDeductionRequestsFromSourceSelectionFactory,
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface $sourceDeductionService
    ) {
        $this->inventory = $inventory;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->sourceDeductionRequestsFromSourceSelectionFactory = $sourceDeductionRequestsFromSourceSelectionFactory;
        $this->sourceDeductionService = $sourceDeductionService;
    }

    public function process(\Magento\Sales\Model\Order $order, \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent)
    {
        $sourceSelectionResult = $this->getSourceSelectionResult($order);
        $sourceDeductionRequests = $this->sourceDeductionRequestsFromSourceSelectionFactory->create(
            $sourceSelectionResult,
            $salesEvent,
            $order->getStore()->getWebsiteId()
        );

        foreach ($sourceDeductionRequests as $sourceDeductionRequest)
        {
            $this->sourceDeductionService->execute($sourceDeductionRequest);
        }
    }

    protected function getSourceSelectionResult(\Magento\Sales\Model\Order $order)
    {
        $inventoryRequest = $this->inventory->getRequestFromOrder($order);

        $selectionAlgorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();
        return $this->sourceSelectionService->execute($inventoryRequest, $selectionAlgorithmCode);
    }
}