<?php

declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $writerConfig */
$writerConfig = Bootstrap::getObjectManager()->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

$resource = Bootstrap::getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class);
$database = $resource->getConnection();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$coreConfigDataTable = "core_config_data";
$websiteCodes = ['eu_website', 'us_website', 'global_website'];

foreach ($websiteCodes as $websiteCode) {
    /** @var Website $website */
    $website = Bootstrap::getObjectManager()->create(Website::class);
    $website->load($websiteCode, 'code');

    $storeIds = $website->getStoreIds();
    foreach ($storeIds as $storeId) {
        $database->query("DELETE FROM $coreConfigDataTable WHERE scope = 'store' AND scope_id = ?", $storeId);
    }

    if ($website->getId()) {
        $website->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
