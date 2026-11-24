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
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * A custom field: a value of the feed that is calculated from the product data. Custom fields are registered in
 * etc/catalog_product_feed_attribute.xml.
 */
abstract class AbstractCustom
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
     */
    public function __construct(
        protected Variables $variables,
        protected Arrays $arrays,
        protected Database $databaseHelper,
        protected Url $urlHelper,
        protected Stores $storeHelper,
        protected Data $productFeedHelper,
        protected Custom $customHelper,
        protected LoggerInterface $logging
    ) {
    }

    /**
     * Returns the value of the field.
     *
     * @param int $storeId
     * @param array $productData
     * @param bool $isChild whether the product is a variant (child, bundled or grouped product)
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
     * @return mixed
     */
    abstract public function process(
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
    ): mixed;

    /**
     * Returns the codes of the product attributes the field needs.
     *
     * @return string[]
     */
    abstract public function requireEavAttributeCodes(): array;

    /**
     * Returns the formatted price, or null if there is no price.
     *
     * @param int $storeId
     * @param mixed $price
     * @param bool $withCurrency
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    protected function formatPrice(int $storeId, mixed $price, bool $withCurrency = false): ?string
    {
        if (! is_numeric($price)) {
            return null;
        }

        return $this->customHelper->formatPrice($storeId, $price, $withCurrency);
    }

    /**
     * Returns whether the product is a configurable, bundle or grouped product, whose price is its minimal price.
     *
     * The price index holds the price of their cheapest variant as minimal price.
     *
     * @param array $productData
     * @phpstan-param array<string, mixed> $productData
     *
     * @return bool
     */
    protected function isCompositeWithMinPrice(array $productData): bool
    {
        $typeId = $productData[ 'type_id' ] ?? null;

        return in_array($typeId, ['configurable', 'bundle', 'grouped'], true);
    }

    /**
     * Returns the URL of the path in the store.
     *
     * @param string $path
     * @param int $storeId
     *
     * @return string
     */
    protected function getDirectUrl(string $path, int $storeId): string
    {
        return $this->urlHelper->getUrl('', null, ['_direct' => $path], $storeId);
    }
}
