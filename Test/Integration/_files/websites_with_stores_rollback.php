<?php

declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $writerConfig */
$writerConfig = Bootstrap::getObjectManager()->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$websiteCodes = ['eu_website', 'us_website', 'global_website'];
foreach ($websiteCodes as $websiteCode)
{
    /** @var Website $website */
    $website = Bootstrap::getObjectManager()->create(Website::class);
    $website->load($websiteCode, 'code');

    $storeIds = $website->getStoreIds();
    foreach($storeIds as $storeId)
    {
        $writerConfig->delete("payone_protect/creditrating/payone_consumerscore_sample_counter", "store", $storeId);
    }

    if ($website->getId())
    {
        $website->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
