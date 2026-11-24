<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model;

use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;
use Infrangible\Core\Helper\Export;

/**
 * An integration writes the exported products into its feed. The export calls, per run:
 * start() once, then per store startStore(), per block of products startBlock(), the export methods of the products
 * and their variants, finishBlock(), then finishStore(), and finish() at the end. The product data arrays come from
 * the export helper of infrangible/m2-core.
 *
 * @see Export::getProductsData()
 */
interface IIntegration // phpcs:ignore Magento2.NamingConvention.InterfaceName
{
    /**
     * Starts the export of the integration.
     *
     * @param string $mode
     * @param int|null $lastRunTime
     *
     * @return void
     */
    public function start(string $mode, ?int $lastRunTime = null): void;

    /**
     * Finishes the export of the integration.
     *
     * @param string $mode
     *
     * @return void
     */
    public function finish(string $mode): void;

    /**
     * Starts the export of the store, with the feed attributes of the integration.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds the products to export, all if empty
     * @param Collection $attributeCollection
     *
     * @return void
     */
    public function startStore(string $mode, int $storeId, array $productIds, Collection $attributeCollection): void;

    /**
     * Finishes the export of the store.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds the products to export, all if empty
     *
     * @return void
     */
    public function finishStore(string $mode, int $storeId, array $productIds): void;

    /**
     * Starts the export of a block of products.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     *
     * @return void
     */
    public function startBlock(string $mode, int $storeId, array $productIds): void;

    /**
     * Finishes the export of a block of products.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     *
     * @return void
     */
    public function finishBlock(string $mode, int $storeId, array $productIds): void;

    /**
     * Exports a product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $productData
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @param array $reviewSummary
     * @param array $children
     * @param array $bundled
     * @param array $grouped
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     * @phpstan-param array<string, mixed> $reviewSummary
     * @phpstan-param array<int|string, array<string, mixed>> $children
     * @phpstan-param array<int|string, array<string, mixed>> $bundled
     * @phpstan-param array<int|string, array<string, mixed>> $grouped
     *
     * @return void
     */
    public function exportProductData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $reviewSummary,
        array $children,
        array $bundled,
        array $grouped
    ): void;

    /**
     * Exports a child of a configurable product; the product data contains the parent product under "parent".
     *
     * @param string $mode
     * @param int $storeId
     * @param array $productData
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     *
     * @return void
     */
    public function exportChildData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void;

    /**
     * Exports a product of a bundle product; the product data contains the bundle product under "parent".
     *
     * @param string $mode
     * @param int $storeId
     * @param array $productData
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     *
     * @return void
     */
    public function exportBundledData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void;

    /**
     * Exports a product of a grouped product; the product data contains the grouped product under "parent".
     *
     * @param string $mode
     * @param int $storeId
     * @param array $productData
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     *
     * @return void
     */
    public function exportGroupedData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): void;

    /**
     * Returns whether the integration is enabled in the store.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId): bool;

    /**
     * Returns whether the integration exports the changed products in the store.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function useForDelta(int $storeId): bool;

    /**
     * Returns the codes of the product attributes the integration needs.
     *
     * @return string[]
     */
    public function getRequiredEavAttributeCodes(): array;

    /**
     * Returns the conditions which product attributes are exported.
     *
     * @return string[]
     * @see Export::getExportableAttributes()
     */
    public function getAttributeConditions(): array;

    /**
     * Returns whether the full export of the integration in the store follows its schedule.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isScheduled(int $storeId): bool;

    /**
     * Returns the cron expressions of the schedule in the store.
     *
     * @param int $storeId
     *
     * @return string[]
     */
    public function getScheduleExpression(int $storeId): array;
}
