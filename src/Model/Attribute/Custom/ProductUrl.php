<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Attribute\Custom;

/**
 * The URL of the product in the store without category, else from its URL path.
 */
class ProductUrl extends AbstractCustom
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
     * @return string
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
    ): string {
        $url = $urlRewrites[ 0 ] ?? null;

        if (! is_string($url) || $url === '') {
            $url = $this->variables->stringValueOrDefault($productData[ 'url_path' ] ?? null);
        }

        return $this->getDirectUrl($url, $storeId);
    }

    /**
     * Returns the codes of the product attributes the field needs.
     *
     * @return string[]
     */
    public function requireEavAttributeCodes(): array
    {
        return ['url_path'];
    }
}
