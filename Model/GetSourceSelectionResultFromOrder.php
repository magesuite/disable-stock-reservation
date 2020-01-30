<?php

namespace MageSuite\DisableStockReservation\Model;

class GetSourceSelectionResultFromOrder
{
    /**
     * @var \Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrder
     */
    protected $inventoryRequestFromOrderFactory;

    /**
     * @var \Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    protected $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var \Magento\InventorySourceSelectionApi\Model\SourceSelectionService
     */
    protected $sourceSelectionService;

    public function __construct(
        \Magento\InventoryShipping\Model\InventoryRequestFromOrderFactory $inventoryRequestFromOrderFactory,
        \Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        \Magento\InventorySourceSelectionApi\Model\SourceSelectionService $sourceSelectionService
    ) {
        $this->inventoryRequestFromOrderFactory = $inventoryRequestFromOrderFactory;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->sourceSelectionService = $sourceSelectionService;
    }

    public function execute(\Magento\Sales\Model\Order $order)
    {
        $inventoryRequest = $this->inventoryRequestFromOrderFactory->create($order);
        $selectionAlgorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();

        return $this->sourceSelectionService->execute($inventoryRequest, $selectionAlgorithmCode);
    }
}