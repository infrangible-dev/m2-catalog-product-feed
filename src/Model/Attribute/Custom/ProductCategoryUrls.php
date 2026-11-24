<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * The URLs of the product in the store, by category ID: the URL without category under the ID of the root
 * category of the store.
 */
class ProductCategoryUrls extends AbstractCustom
{
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
     * @return array
     * @phpstan-return array<int|string, string>
     * @throws NoSuchEntityException
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
    ): array {
        $result = [];

        foreach ($urlRewrites as $categoryId => $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            if ((int)$categoryId === 0) {
                $categoryId = $this->variables->intValue($this->storeHelper->getStore($storeId)->getRootCategoryId());
            }

            $result[ $categoryId ] = $this->getDirectUrl($url, $storeId);
        }

        return $result;
    }

    /**
     * Returns the codes of the product attributes the field needs.
     *
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return [];
    }
}
