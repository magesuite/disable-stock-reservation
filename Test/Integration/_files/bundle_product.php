<?php

declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(\Magento\Catalog\Api\Data\ProductInterfaceFactory::class);

$extensionAttributesFactory = $objectManager->get(\Magento\Framework\Api\ExtensionAttributesFactory::class);
$bundleOptionFactory = $objectManager->get(\Magento\Bundle\Api\Data\OptionInterfaceFactory::class);
$productLinkFactory = $objectManager->get(\Magento\Bundle\Api\Data\LinkInterfaceFactory::class);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productRepository->cleanCache();

$productsData = [
    [
        'attributes'        => [
            'attribute_set_id' => 4,
            'type_id'          => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
            'sku'              => 'SKU-BUNDLE-1',
            'name'             => 'Bundle Product Blue',
            'status'           => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
            'stock_data'       => ['is_in_stock' => true]
        ],
        'custom_attributes' => [
            'price_type'    => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
            'shipment_type' => \Magento\Catalog\Model\Product\Type\AbstractType::SHIPMENT_SEPARATELY,
            'sku_type'      => 0,
            'price_view'    => 1
        ],
        'simple_links'      => [
            [
                'sku'   => 'SKU-1',
                'qty'   => 1,
                'title' => 'Simple Product Orange'
            ],
            [
                'sku'   => 'SKU-3',
                'qty'   => 1,
                'title' => 'Simple Product Blue'
            ]
        ]
    ],
    [
        'attributes'        => [
            'attribute_set_id' => 4,
            'type_id'          => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
            'sku'              => 'SKU-BUNDLE-2',
            'name'             => 'Bundle Product White',
            'status'           => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
            'stock_data'       => ['is_in_stock' => true]
        ],
        'custom_attributes' => [
            'price_type'    => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
            'shipment_type' => \Magento\Catalog\Model\Product\Type\AbstractType::SHIPMENT_TOGETHER,
            'sku_type'      => 0,
            'price_view'    => 1
        ],
        'simple_links'      => [
            [
                'sku'   => 'SKU-2',
                'qty'   => 1,
                'title' => 'Simple Product White'
            ],
            [
                'sku'   => 'SKU-3',
                'qty'   => 1,
                'title' => 'Simple Product Blue'
            ]
        ]
    ]
];

foreach ($productsData as $productData) {
    /** @var \Magento\Catalog\Model\Product $product */
    $product = $productFactory->create();
    foreach ($productData['attributes'] as $code => $value) {
        $product->setDataUsingMethod($code, $value);
    }
    $product->setCustomAttributes($productData['custom_attributes']);

    $options = [];
    foreach ($productData['simple_links'] as $linkData) {
        /** @var Magento\Bundle\Api\Data\LinkInterface $link */
        $link = $productLinkFactory->create();
        $link->setSku($linkData['sku']);
        $link->setQty($linkData['qty']);
        $link->setCanChangeQuantity(1);

        /** @var Magento\Bundle\Api\Data\OptionInterface $option */
        $option = $bundleOptionFactory->create();
        $option->setTitle($linkData['title']);
        $option->setRequired(true);
        $option->setType('select');
        $option->setProductLinks([$link]);
        $options[] = $option;
    }

    /** @var \Magento\Catalog\Api\Data\ProductExtensionInterface $extensionAttributes */
    $extensionAttributes = $extensionAttributesFactory->create(\Magento\Catalog\Api\Data\ProductInterface::class);
    $extensionAttributes->setBundleProductOptions($options);

    $product->setExtensionAttributes($extensionAttributes);
    $product = $productRepository->save($product);
}
