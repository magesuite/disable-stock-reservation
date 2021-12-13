<?php

declare(strict_types=1);

$websiteCodes = ['eu_website', 'us_website', 'global_website'];
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/**
 * Set original sequence builder to object manager in order to generate sequence table with correct store id.
 */
$sequenceBuilder = $objectManager->get(\Magento\InventorySalesApi\Test\OriginalSequenceBuilder::class);
$objectManager->addSharedInstance($sequenceBuilder, \Magento\SalesSequence\Model\Builder::class);
/** @var \Magento\SalesSequence\Model\EntityPool $entityPool */
$entityPool = $objectManager->get(\Magento\SalesSequence\Model\EntityPool::class);
/** @var \Magento\SalesSequence\Model\Config $sequenceConfig */
$sequenceConfig = $objectManager->get(\Magento\SalesSequence\Model\Config::class);
/** @var Magento\Framework\App\RequestInterface $request */
$request = $objectManager->get(\Magento\Framework\App\RequestInterface::class);

/** @var \Magento\Store\Api\Data\StoreInterface $store */
$store = $objectManager->create(\Magento\Store\Model\Store::class);
$store->load('default');
$rootCategoryId = $store->getRootCategoryId();

foreach ($websiteCodes as $key => $websiteCode) {
    $params = [
        'code' => $websiteCode,
        'name' => 'Test Website ' . $websiteCode,
        'is_default' => '0',
    ];

    /** @var \Magento\Store\Model\Website $website */
    $website = $objectManager->create(\Magento\Store\Model\Website::class);
    $website->setData($params);
    $request->setParams(["website" => $params]); //fix for cleverreach module
    $website->save();

    $store = $objectManager->create(\Magento\Store\Model\Store::class);
    $store->setCode(
        'store_for_' . $websiteCode
    )->setWebsiteId(
        $website->getId()
    )->setName(
        'store_for_' . $websiteCode
    )->setSortOrder(
        10 + $key
    )->setIsActive(
        1
    );

    /** @var \Magento\Store\Api\Data\GroupInterface $group */
    $group = $objectManager->create(\Magento\Store\Api\Data\GroupInterface::class);
    $group->setName('store_view_' . $websiteCode);
    $group->setCode('store_view_' . $websiteCode);
    $group->setWebsiteId($website->getId());
    $group->setDefaultStoreId($store->getId());
    $group->setRootCategoryId($rootCategoryId);
    $group->save();

    $website->setDefaultGroupId($group->getId());
    $website->save();
    $store->setGroupId($group->getId());
    $store->save();
}

/**
 * Revert set original sequence builder to test sequence builder.
 */
$sequenceBuilder = $objectManager->get(\Magento\TestFramework\Db\Sequence\Builder::class);
$objectManager->addSharedInstance($sequenceBuilder, \Magento\SalesSequence\Model\Builder::class);
