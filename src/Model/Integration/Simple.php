<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Integration;

use Exception;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;

/**
 * Base of integrations that write every product and variant with the values of their feed attributes: integrations
 * implement exportProduct(), exportChild(), exportBundled() and exportGrouped().
 */
abstract class Simple extends Base
{
    /** @var int|null */
    private ?int $lastRunTime = null;

    /**
     * Remembers the time of the last run.
     *
     * @param string $mode
     * @param int|null $lastRunTime
     *
     * @return void
     */
    public function start(string $mode, ?int $lastRunTime = null): void
    {
        $this->setLastRunTime($lastRunTime);
    }

    /**
     * Returns the time of the last run, or null if the schedules are ignored.
     *
     * @return int|null
     */
    protected function getLastRunTime(): ?int
    {
        return $this->lastRunTime;
    }

    /**
     * Sets the time of the last run.
     *
     * @param int|null $lastRunTime
     *
     * @return void
     */
    protected function setLastRunTime(?int $lastRunTime): void
    {
        $this->lastRunTime = $lastRunTime;
    }

    /**
     * Collects the feed attributes of the store.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     * @param Collection $attributeCollection
     *
     * @return void
     * @throws Exception
     */
    public function startStore(string $mode, int $storeId, array $productIds, Collection $attributeCollection): void
    {
        $this->setupAttributes($attributeCollection);
    }

    /**
     * Starts the export of a block of products: nothing to do.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- integrations override it if they need to
    public function startBlock(string $mode, int $storeId, array $productIds): void
    {
    }

    /**
     * Exports a product with the values of the feed attributes of products.
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
    ): void {
        $exportData = $this->getExportData(
            $mode,
            $this->getProductAttributeCodes(),
            $storeId,
            $productData,
            false,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            $reviewSummary,
            $children,
            $bundled,
            $grouped
        );

        if (count($exportData) > 0) {
            $this->exportProduct($mode, $storeId, $exportData);
        }
    }

    /**
     * Writes the values of a product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    abstract protected function exportProduct(string $mode, int $storeId, array $exportData): void;

    /**
     * Exports a child of a configurable product with the values of the feed attributes of variants.
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
    ): void {
        $exportData = $this->getVariantExportData(
            $mode,
            $storeId,
            $productData,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem
        );

        if (count($exportData) > 0) {
            $this->exportChild($mode, $storeId, $exportData);
        }
    }

    /**
     * Writes the values of a child of a configurable product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    abstract protected function exportChild(string $mode, int $storeId, array $exportData): void;

    /**
     * Exports a product of a bundle product with the values of the feed attributes of variants.
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
    ): void {
        $exportData = $this->getVariantExportData(
            $mode,
            $storeId,
            $productData,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem
        );

        if (count($exportData) > 0) {
            $this->exportBundled($mode, $storeId, $exportData);
        }
    }

    /**
     * Writes the values of a product of a bundle product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    abstract protected function exportBundled(string $mode, int $storeId, array $exportData): void;

    /**
     * Exports a product of a grouped product with the values of the feed attributes of variants.
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
    ): void {
        $exportData = $this->getVariantExportData(
            $mode,
            $storeId,
            $productData,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem
        );

        if (count($exportData) > 0) {
            $this->exportGrouped($mode, $storeId, $exportData);
        }
    }

    /**
     * Writes the values of a product of a grouped product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    abstract protected function exportGrouped(string $mode, int $storeId, array $exportData): void;

    /**
     * Finishes the export of a block of products: nothing to do.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- integrations override it if they need to
    public function finishBlock(string $mode, int $storeId, array $productIds): void
    {
    }

    /**
     * Finishes the export of the store: nothing to do.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- integrations override it if they need to
    public function finishStore(string $mode, int $storeId, array $productIds): void
    {
    }

    /**
     * Finishes the export: nothing to do.
     *
     * @param string $mode
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- integrations override it if they need to
    public function finish(string $mode): void
    {
    }

    /**
     * Returns the values of the feed attributes of variants for a variant.
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
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function getVariantExportData(
        string $mode,
        int $storeId,
        array $productData,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem
    ): array {
        return $this->getExportData(
            $mode,
            $this->getVariantAttributeCodes(),
            $storeId,
            $productData,
            true,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            [],
            [],
            [],
            []
        );
    }
}
