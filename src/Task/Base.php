<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Task;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\ExportValidationFactory;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\RunFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * Base of the product feed tasks: exports the products of the stores with the integrations.
 *
 * With the admin store (the default of the commands and the cron job), the task exports the products of all stores.
 */
abstract class Base extends \Infrangible\Task\Task\Base
{
    /** Keys of the product data that hold related data instead of attribute values. */
    private const RELATED_DATA_KEYS = [
        'category_paths',
        'url_rewrites',
        'gallery_images',
        'indexed_prices',
        'stock_item',
        'review_summary',
        'children',
        'bundled',
        'grouped'
    ];

    /** @var string[] the integrations to use, all if empty */
    private array $integrationNames = [];

    /** @var IIntegration[] the started integrations by name */
    private array $integrations = [];

    /** @var bool */
    private bool $force = false;

    /** @var bool */
    private bool $emptyRun = true;

    /**
     * @param File $file
     * @param Registry $registryHelper
     * @param \Infrangible\Task\Helper\Data $helper
     * @param LoggerInterface $logging
     * @param DirectoryList $directoryList
     * @param RunFactory $runFactory
     * @param RunResourceFactory $runResourceFactory
     * @param MailFactory $mailFactory
     * @param Variables $variables
     * @param Stores $storeHelper
     * @param Database $databaseHelper
     * @param Export $exportHelper
     * @param Data $productFeedHelper
     * @param CollectionFactory $attributeCollectionFactory
     * @param \Infrangible\CatalogIndexerFlat\Helper\Data $catalogIndexerFlatHelper
     * @param ExportValidationFactory $exportValidationFactory
     * @param ManagerInterface $eventManager
     * @param ChangeLog $productFeedChangeLogHelper
     */
    public function __construct(
        File $file,
        Registry $registryHelper,
        \Infrangible\Task\Helper\Data $helper,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        RunFactory $runFactory,
        RunResourceFactory $runResourceFactory,
        MailFactory $mailFactory,
        protected Variables $variables,
        protected Stores $storeHelper,
        protected Database $databaseHelper,
        protected Export $exportHelper,
        protected Data $productFeedHelper,
        protected CollectionFactory $attributeCollectionFactory,
        protected \Infrangible\CatalogIndexerFlat\Helper\Data $catalogIndexerFlatHelper,
        protected ExportValidationFactory $exportValidationFactory,
        protected ManagerInterface $eventManager,
        protected ChangeLog $productFeedChangeLogHelper
    ) {
        parent::__construct(
            $file,
            $registryHelper,
            $helper,
            $logging,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $mailFactory
        );
    }

    /**
     * Returns the integrations to use, all if empty.
     *
     * @return string[]
     */
    public function getIntegrationNames(): array
    {
        return $this->integrationNames;
    }

    /**
     * Sets the integrations to use, all if empty.
     *
     * @param string[] $integrationNames
     *
     * @return void
     */
    public function setIntegrationNames(array $integrationNames): void
    {
        $this->integrationNames = $integrationNames;
    }

    /**
     * Returns whether the schedules of the integrations are ignored.
     *
     * @return bool
     */
    public function isForce(): bool
    {
        return $this->force;
    }

    /**
     * Sets whether the schedules of the integrations are ignored.
     *
     * @param bool $force
     *
     * @return void
     */
    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    /**
     * Prepares the task: no integration exported yet.
     *
     * @return void
     */
    protected function prepare(): void
    {
        $this->setIsEmptyRun(true);
    }

    /**
     * Exports the products with the integrations; called by the tasks.
     *
     * @param string $mode
     * @param string|null $storeCode the store, all stores if empty or the admin store
     * @param int[] $productIds the products to export, all if empty
     * @param string[] $integrationNames the integrations to use, all of the store if empty
     * @param int|null $lastRunTime time of the last run, null to ignore the schedules
     *
     * @return void
     * @throws Exception
     */
    public function reindexProducts(
        string $mode,
        ?string $storeCode = null,
        array $productIds = [],
        array $integrationNames = [],
        ?int $lastRunTime = null
    ): void {
        foreach ($this->getExportStoreIds($storeCode) as $storeId) {
            $this->reindexStoreProducts(
                $mode,
                $storeId,
                $productIds,
                count($integrationNames) > 0 ? $integrationNames :
                    $this->productFeedHelper->getAllIntegrationNames($storeId),
                $lastRunTime
            );
        }

        foreach ($this->integrations as $integration) {
            $integration->finish($mode);
        }

        $this->integrations = [];
    }

    /**
     * Returns the IDs of the stores to export: the store, or all stores without the admin store.
     *
     * @param string|null $storeCode
     *
     * @return int[]
     * @throws NoSuchEntityException
     */
    protected function getExportStoreIds(?string $storeCode): array
    {
        if ($storeCode !== null && $storeCode !== '') {
            $storeId = $this->variables->intValue($this->storeHelper->getStore($storeCode)->getId());

            if ($storeId !== 0) {
                return [$storeId];
            }
        }

        return array_values(
            array_map(
                fn(Store $store): int => $this->variables->intValue($store->getId()),
                $this->storeHelper->getStores()
            )
        );
    }

    /**
     * Exports the products of the store with its integrations that export in this run.
     *
     * Integrations are started once per run.
     *
     * @param string $mode
     * @param int $storeId
     * @param int[] $productIds
     * @param string[] $integrationNames
     * @param int|null $lastRunTime
     *
     * @return void
     * @throws Exception
     */
    private function reindexStoreProducts(
        string $mode,
        int $storeId,
        array $productIds,
        array $integrationNames,
        ?int $lastRunTime
    ): void {
        $integrations = $this->productFeedHelper->getIntegrations($mode, $storeId, $integrationNames, $lastRunTime);

        if (count($integrations) === 0) {
            return;
        }

        $this->setIsEmptyRun(false);

        foreach ($integrations as $integrationName => $integration) {
            if (! array_key_exists($integrationName, $this->integrations)) {
                $this->integrations[ $integrationName ] = $integration;

                $integration->start($mode, $lastRunTime);
            }
        }

        $this->rebuildStoreIndex($mode, $storeId, $integrations, $productIds);
    }

    /**
     * Exports the products of the store with the integrations.
     *
     * The flat product index is disabled meanwhile, since it holds only some attributes.
     *
     * @param string $mode
     * @param int $storeId
     * @param IIntegration[] $integrations
     * @param int[] $productIds the products to export, all if empty
     *
     * @return void
     */
    private function rebuildStoreIndex(string $mode, int $storeId, array $integrations, array $productIds): void
    {
        if (PHP_SAPI === 'cli') {
            $this->registryHelper->register('custom_entry_point', true, true);
        }

        foreach ($integrations as $integrationName => $integration) {
            $attributes = $this->attributeCollectionFactory->create();

            $attributes->filterByIntegration($integrationName);

            if ($mode === Data::MODE_DELTA) {
                $attributes->filterDeltaAttributes();
            }

            $attributes->orderByPosition();

            $integration->startStore($mode, $storeId, $productIds, $attributes);
        }

        $this->catalogIndexerFlatHelper->disableProductFlatIndexer();

        try {
            $this->exportStoreProducts($mode, $storeId, $integrations, $productIds);

            $this->logging->debug('Finished indexing');
        } catch (Exception $exception) {
            $this->logging->error(
                sprintf('Could not rebuild store with id: %d because: %s', $storeId, $exception->getMessage())
            );
            $this->logging->error($exception);
        } finally {
            $this->catalogIndexerFlatHelper->removeDisableProductFlatIndexer();
        }

        foreach ($integrations as $integration) {
            $integration->finishStore($mode, $storeId, $productIds);
        }
    }

    /**
     * Exports the products of the store with the integrations, in blocks of products.
     *
     * @param string $mode
     * @param int $storeId
     * @param IIntegration[] $integrations
     * @param int[] $productIds the products to export, all if empty
     *
     * @return void
     * @throws Exception
     */
    private function exportStoreProducts(string $mode, int $storeId, array $integrations, array $productIds): void
    {
        $dbAdapter = $this->databaseHelper->getDefaultConnection();

        $attributeConditions = $this->productFeedHelper->getAttributeConditions($integrations);
        $requiredEavAttributeCodes = $this->productFeedHelper->getAdditionalEavAttributeCodes($integrations);

        if (count($productIds) === 0) {
            $this->logging->info(sprintf('Indexing all products for store with id: %d', $storeId));
        } else {
            $this->logging->info(
                sprintf('Indexing %d product(s) for store with id: %d', count($productIds), $storeId)
            );
        }

        $showOutOfStock = $this->storeHelper->getStoreConfigFlag(
            'cataloginventory/options/show_out_of_stock',
            false,
            $storeId
        );
        $limitActiveCategoriesToStore = $this->storeHelper->getStoreConfigFlag(
            'infrangible_catalogproductfeed/export/limit_active_categories_to_store',
            true,
            $storeId
        );

        $lastProductId = 0;

        while (true) {
            $this->logging->debug(sprintf('Fetched from database with product id > %d', $lastProductId));

            $chunkProductIds = $this->exportHelper->getExportableProductIds(
                $storeId,
                ! $showOutOfStock,
                $productIds,
                $lastProductId
            );

            if (count($chunkProductIds) === 0) {
                break;
            }

            foreach ($integrations as $integration) {
                $integration->startBlock($mode, $storeId, $chunkProductIds);
            }

            $this->logging->debug(sprintf('Fetched %d product(s) from database', count($chunkProductIds)));

            $productsData = $this->exportHelper->getProductsData(
                $dbAdapter,
                $chunkProductIds,
                $storeId,
                $attributeConditions,
                $requiredEavAttributeCodes,
                $limitActiveCategoriesToStore
            );

            foreach ($productsData as $productData) {
                $this->exportProduct($mode, $storeId, $integrations, $productData);
            }

            foreach ($integrations as $integration) {
                $integration->finishBlock($mode, $storeId, $chunkProductIds);
            }

            $lastProductId = (int)end($chunkProductIds);
        }
    }

    /**
     * Exports the product and its variants with the integrations, unless an observer rejects it.
     *
     * Observers of the event infrangible_catalogproductfeed_export_validation reject a product with setResult(false).
     *
     * @param string $mode
     * @param int $storeId
     * @param IIntegration[] $integrations
     * @param array $productData
     * @phpstan-param array<string, mixed> $productData
     *
     * @return void
     */
    private function exportProduct(string $mode, int $storeId, array $integrations, array $productData): void
    {
        $categoryPaths = $this->getRelatedRows($productData, 'category_paths');
        $urlRewrites = $this->getRelatedData($productData, 'url_rewrites');
        $galleryImages = $this->getRelatedData($productData, 'gallery_images');
        $indexedPrices = $this->getRelatedRecord($productData, 'indexed_prices');
        $stockItem = $this->getRelatedRecord($productData, 'stock_item');
        $reviewSummary = $this->getRelatedRecord($productData, 'review_summary');
        $children = $this->getRelatedRows($productData, 'children');
        $bundled = $this->getRelatedRows($productData, 'bundled');
        $grouped = $this->getRelatedRows($productData, 'grouped');

        $productData = array_diff_key($productData, array_flip(self::RELATED_DATA_KEYS));

        $exportValidation = $this->exportValidationFactory->create();

        $exportValidation->setMode($mode);
        $exportValidation->setStoreId($storeId);
        $exportValidation->setProductData($productData);
        $exportValidation->setCategoryPaths($categoryPaths);
        $exportValidation->setUrlRewrites($urlRewrites);
        $exportValidation->setGalleryImages($galleryImages);
        $exportValidation->setIndexedPrices($indexedPrices);
        $exportValidation->setStockItem($stockItem);
        $exportValidation->setReviewSummary($reviewSummary);
        $exportValidation->setChildren($children);
        $exportValidation->setBundled($bundled);
        $exportValidation->setGrouped($grouped);
        $exportValidation->setResult(true);

        $this->eventManager->dispatch(
            'infrangible_catalogproductfeed_export_validation',
            ['export_validation' => $exportValidation]
        );

        $exported = $exportValidation->getResult();

        $categoryUrls = array_map(
            fn(array $categoryPath): string => $this->variables->stringValueOrDefault($categoryPath[ 'url' ] ?? null),
            $categoryPaths
        );

        $this->logging->info(
            sprintf(
                '%s product with sku: %s with name: %s, final price: %s, category urls: %s, in stock: %s ' .
                'in store with id: %d',
                $exported ? 'Exporting' : 'Skipping',
                $this->variables->stringValueOrDefault($productData[ 'sku' ] ?? null),
                $this->variables->stringValueOrDefault($productData[ 'name' ] ?? null),
                $this->variables->stringValueOrDefault($indexedPrices[ 'final_price' ] ?? null),
                implode(', ', $categoryUrls),
                var_export($this->isInStock($stockItem), true),
                $storeId
            )
        );

        if (! $exported) {
            return;
        }

        foreach ($integrations as $integration) {
            $integration->exportProductData(
                $mode,
                $storeId,
                $productData,
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

            foreach ($children as $childProductData) {
                $this->exportVariant($mode, $storeId, $integration, 'child', $productData, $childProductData);
            }

            foreach ($bundled as $bundledProductData) {
                $this->exportVariant($mode, $storeId, $integration, 'bundled', $productData, $bundledProductData);
            }

            foreach ($grouped as $groupedProductData) {
                $this->exportVariant($mode, $storeId, $integration, 'grouped', $productData, $groupedProductData);
            }
        }
    }

    /**
     * Exports a variant (child, bundled or grouped product) of the product with the integration.
     *
     * Children use the categories and URLs of their parent, bundled and grouped products their own.
     *
     * @param string $mode
     * @param int $storeId
     * @param IIntegration $integration
     * @param string $type child, bundled or grouped
     * @param array $parentProductData
     * @param array $variantProductData
     * @phpstan-param array<string, mixed> $parentProductData
     * @phpstan-param array<string, mixed> $variantProductData
     *
     * @return void
     */
    private function exportVariant(
        string $mode,
        int $storeId,
        IIntegration $integration,
        string $type,
        array $parentProductData,
        array $variantProductData
    ): void {
        $categoryData = $type === 'child' ? $parentProductData : $variantProductData;

        $categoryPaths = $this->getRelatedRows($categoryData, 'category_paths');
        $urlRewrites = $this->getRelatedData($categoryData, 'url_rewrites');
        $galleryImages = $this->getRelatedData($variantProductData, 'gallery_images');
        $indexedPrices = $this->getRelatedRecord($variantProductData, 'indexed_prices');
        $stockItem = $this->getRelatedRecord($variantProductData, 'stock_item');

        $variantProductData = array_diff_key($variantProductData, array_flip(self::RELATED_DATA_KEYS));

        $variantProductData[ 'parent' ] = $parentProductData;

        $this->logging->info(
            sprintf(
                'Exporting %s with sku: %s with name: %s, final price: %s, in stock: %s in store with id: %d',
                $type,
                $this->variables->stringValueOrDefault($variantProductData[ 'sku' ] ?? null),
                $this->variables->stringValueOrDefault($variantProductData[ 'name' ] ?? null),
                $this->variables->stringValueOrDefault($indexedPrices[ 'final_price' ] ?? null),
                var_export($this->isInStock($stockItem), true),
                $storeId
            )
        );

        if ($type === 'child') {
            $integration->exportChildData(
                $mode,
                $storeId,
                $variantProductData,
                $categoryPaths,
                $urlRewrites,
                $galleryImages,
                $indexedPrices,
                $stockItem
            );
        } elseif ($type === 'bundled') {
            $integration->exportBundledData(
                $mode,
                $storeId,
                $variantProductData,
                $categoryPaths,
                $urlRewrites,
                $galleryImages,
                $indexedPrices,
                $stockItem
            );
        } else {
            $integration->exportGroupedData(
                $mode,
                $storeId,
                $variantProductData,
                $categoryPaths,
                $urlRewrites,
                $galleryImages,
                $indexedPrices,
                $stockItem
            );
        }
    }

    /**
     * Returns the related data of the product, for example its URL rewrites by category ID.
     *
     * @param array $productData
     * @param string $key
     * @phpstan-param array<string, mixed> $productData
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    private function getRelatedData(array $productData, string $key): array
    {
        $data = $productData[ $key ] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * Returns a related record of the product with field names, for example its stock item.
     *
     * @param array $productData
     * @param string $key
     * @phpstan-param array<string, mixed> $productData
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function getRelatedRecord(array $productData, string $key): array
    {
        return $this->toRecord($this->getRelatedData($productData, $key));
    }

    /**
     * Returns the related records of the product, for example its children by product ID.
     *
     * @param array $productData
     * @param string $key
     * @phpstan-param array<string, mixed> $productData
     *
     * @return array
     * @phpstan-return array<int|string, array<string, mixed>>
     */
    private function getRelatedRows(array $productData, string $key): array
    {
        return array_map(
            $this->toRecord(...),
            array_filter($this->getRelatedData($productData, $key), 'is_array')
        );
    }

    /**
     * Returns the record with its keys as field names.
     *
     * @param array $record
     * @phpstan-param array<int|string, mixed> $record
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function toRecord(array $record): array
    {
        return array_combine(array_map('strval', array_keys($record)), array_values($record));
    }

    /**
     * Returns whether the stock item is in stock.
     *
     * @param array $stockItem
     * @phpstan-param array<string, mixed> $stockItem
     *
     * @return bool
     */
    private function isInStock(array $stockItem): bool
    {
        $isInStock = $stockItem[ 'is_in_stock' ] ?? 0;

        return is_numeric($isInStock) && (int)$isInStock === 1;
    }

    /**
     * Finishes the task: nothing to do.
     *
     * @param bool $success
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock -- the full export writes its run time
    protected function dismantle(bool $success): void
    {
    }

    /**
     * Returns whether no integration exported in this run.
     *
     * @return bool
     */
    public function isEmptyRun(): bool
    {
        return $this->emptyRun;
    }

    /**
     * Sets whether no integration exported in this run.
     *
     * @param bool $isEmptyRun
     *
     * @return void
     */
    public function setIsEmptyRun(bool $isEmptyRun): void
    {
        $this->emptyRun = $isEmptyRun;
    }
}
