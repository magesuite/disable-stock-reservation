<?php

declare(strict_types=1);

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $writerConfig */
$writerConfig = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

$resource = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class);
$database = $resource->getConnection();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$coreConfigDataTable = "core_config_data";
$websiteCodes = ['eu_website', 'us_website', 'global_website'];

foreach ($websiteCodes as $websiteCode) {
    /** @var \Magento\Store\Model\Website $website */
    $website = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Store\Model\Website::class);
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
