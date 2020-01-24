<?php

namespace MageSuite\DisableStockReservation\Service;

class SourceDeductionManager
{
    /**
     * @var \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface
     */
    protected $sourceDeductionService;

    /**
     * @var \MageSuite\DisableStockReservation\Model\GetSourceSelectionResultFromOrder
     */
    protected $getSourceSelectionResultFromOrder;

    /**
     * @var \Magento\InventorySales\Model\ReturnProcessor\GetSourceDeductionRequestFromSourceSelection
     */
    protected $getSourceDeductionRequestFromSourceSelection;

    public function __construct(
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface $sourceDeductionService,
        \MageSuite\DisableStockReservation\Model\GetSourceSelectionResultFromOrder $getSourceSelectionResultFromOrder,
        \Magento\InventorySales\Model\ReturnProcessor\GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection
    ) {
        $this->sourceDeductionService = $sourceDeductionService;
        $this->getSourceSelectionResultFromOrder = $getSourceSelectionResultFromOrder;
        $this->getSourceDeductionRequestFromSourceSelection = $getSourceDeductionRequestFromSourceSelection;
    }

    public function process(\Magento\Sales\Model\Order $order)
    {
        $sourceSelectionResults = $this->getSourceSelectionResultFromOrder->execute($order);
        $sourceDeductionRequests = $this->getSourceDeductionRequestFromSourceSelection->execute($order, $sourceSelectionResults);

        foreach ($sourceDeductionRequests as $sourceDeductionRequest) {
            $this->sourceDeductionService->execute($sourceDeductionRequest);
        }
    }
}