<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Wishlist\Model\Product;

use Magento\Bundle\Helper\Catalog\Product\Configuration;
use Magento\Bundle\Model\Option;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Wishlist\Model\Item;

/**
 * Data provider for bundled product options
 */
class BundleOptionDataProvider
{
    /**
     * @var Data
     */
    private $pricingHelper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Data $pricingHelper
     * @param SerializerInterface $serializer
     * @param Configuration $configuration
     */
    public function __construct(
        Data $pricingHelper,
        SerializerInterface $serializer,
        Configuration $configuration
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->serializer = $serializer;
        $this->configuration = $configuration;
    }

    /**
     * Extract data for a bundled wishlist item
     *
     * @param Item $item
     *
     * @return array
     */
    public function getData(Item $item): array
    {
        $options = [];
        $product = $item->getProduct();
        $optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');
        $bundleOptionsIds = $optionsQuoteItemOption
            ? $this->serializer->unserialize($optionsQuoteItemOption->getValue())
            : [];

        /** @var Type $typeInstance */
        $typeInstance = $product->getTypeInstance();

        if ($bundleOptionsIds) {
            $selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');
            $optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);
            $bundleSelectionIds = $this->serializer->unserialize($selectionsQuoteItemOption->getValue());

            if (!empty($bundleSelectionIds)) {
                $selectionsCollection = $typeInstance->getSelectionsByIds($bundleSelectionIds, $product);
                $bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);

                $options = $this->buildBundleOptions($bundleOptions, $item);
            }
        }

        return $options;
    }

    /**
     * Build bundle product options based on current selection
     *
     * @param Option[] $bundleOptions
     * @param Item $item
     *
     * @return array
     */
    private function buildBundleOptions(array $bundleOptions, Item $item): array
    {
        $options = [];
        foreach ($bundleOptions as $bundleOption) {
            if (!$bundleOption->getSelections()) {
                continue;
            }

            $options[] = [
                'id' => $bundleOption->getId(),
                'label' => $bundleOption->getTitle(),
                'type' => $bundleOption->getType(),
                'values' => $this->buildBundleOptionValues($bundleOption->getSelections(), $item),
            ];
        }

        return $options;
    }

    /**
     * Build bundle product option values based on current selection
     *
     * @param Product[] $selections
     * @param Item $item
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function buildBundleOptionValues(array $selections, Item $item): array
    {
        $product = $item->getProduct();
        $values = [];

        foreach ($selections as $selection) {
            $qty = (float) $this->configuration->getSelectionQty($product, $selection->getSelectionId());
            if (!$qty) {
                continue;
            }

            $selectionPrice = $this->configuration->getSelectionFinalPrice($item, $selection);
            $values[] = [
                'label' => $selection->getName(),
                'id' => $selection->getSelectionId(),
                'quantity' => $qty,
                'price' => $this->pricingHelper->currency($selectionPrice, false, false),
            ];
        }

        return $values;
    }
}
