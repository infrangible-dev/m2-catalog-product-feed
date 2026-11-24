<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Psr\Log\LoggerInterface;

/**
 * The URL of the product in the store from its URL key and the URL suffix of products in the store.
 */
class ProductUrlWithKey extends AbstractCustom
{
    /**
     * @param Variables $variables
     * @param Arrays $arrays
     * @param Database $databaseHelper
     * @param Url $urlHelper
     * @param Stores $storeHelper
     * @param Data $productFeedHelper
     * @param Custom $customHelper
     * @param LoggerInterface $logging
     * @param Product $productHelper
     */
    public function __construct(
        Variables $variables,
        Arrays $arrays,
        Database $databaseHelper,
        Url $urlHelper,
        Stores $storeHelper,
        Data $productFeedHelper,
        Custom $customHelper,
        LoggerInterface $logging,
        protected Product $productHelper
    ) {
        parent::__construct(
            $variables,
            $arrays,
            $databaseHelper,
            $urlHelper,
            $storeHelper,
            $productFeedHelper,
            $customHelper,
            $logging
        );
    }

    /**
     * Returns the value of the field.
     *
     * @param int $storeId
     * @param array $productData
     * @param bool $isChild
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @param array $children
     * @param array $bundled
     * @param array $grouped
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     * @phpstan-param array<int|string, array<string, mixed>> $children
     * @phpstan-param array<int|string, array<string, mixed>> $bundled
     * @phpstan-param array<int|string, array<string, mixed>> $grouped
     *
     * @return string|null
     */
    public function process(
        int $storeId,
        array $productData,
        bool $isChild,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $children,
        array $bundled,
        array $grouped
    ): ?string {
        $urlKey = $productData[ 'url_key' ] ?? null;

        if (! is_string($urlKey) || $urlKey === '') {
            return null;
        }

        $product = $this->productHelper->newProduct();

        $urlSuffix = $this->storeHelper->getStoreConfig(
            ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX,
            '',
            false,
            $storeId
        );

        return $this->getDirectUrl(
            $product->formatUrlKey($urlKey) . (is_string($urlSuffix) ? $urlSuffix : ''),
            $storeId
        );
    }

    /**
     * Returns the codes of the product attributes the field needs.
     *
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['url_key'];
    }
}
