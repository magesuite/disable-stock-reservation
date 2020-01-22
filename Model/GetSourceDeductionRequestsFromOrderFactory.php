<?php

namespace MageSuite\DisableStockReservation\Model;

class GetSourceDeductionRequestsFromOrderFactory
{
    /**
     * @var \Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory
     */
    protected $sourceDeductionRequestInterface;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory
     */
    protected $salesChannelInterface;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory
     */
    protected $eventInterface;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory
     */
    protected $salesEventFactory;

    /**
     * @var \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface
     */
    protected $defaultSourceProvider;

    public function __construct(
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory $sourceDeductionRequestInterface,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory $salesChannelInterface,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory $eventInterface,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory $salesEventFactory
    ) {
        $this->sourceDeductionRequestInterface = $sourceDeductionRequestInterface;
        $this->salesChannelInterface = $salesChannelInterface;
        $this->eventInterface = $eventInterface;
        $this->websiteRepository = $websiteRepository;
        $this->salesEventFactory = $salesEventFactory;
    }

    public function execute(\Magento\Sales\Model\Order $order, string $sourceCode, array $items) : \Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface
    {
        $websiteId = $order->getStore()->getWebsiteId();
        $salesEvent = $this->salesEventFactory->create(
            [
                'type' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::EVENT_ORDER_PLACED,
                'objectType' => \Magento\InventorySalesApi\Api\Data\SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId' => $order->getEntityId(),
            ]
        );

        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $salesChannel = $this->salesChannelInterface->create([
            'data' => [
                'type' => \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
                "code" => $websiteCode
            ]
        ]);

        return $this->sourceDeductionRequestInterface->create([
           "sourceCode" => $sourceCode,
           "items" => $items,
           "salesChannel" => $salesChannel,
           "salesEvent" => $salesEvent
        ]);
    }
}