<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog\CollectionFactory;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\EntityType;
use Infrangible\Core\Helper\Models;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * The change log of the delta export: the IDs of the products whose exported data changed.
 */
class ChangeLog
{
    /** Codes of the attributes that change the price of a product. */
    public const PRICE_ATTRIBUTES = [
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'group_price',
        'tier_price'
    ];

    /** Attributes that change with every save, without changing the exported data. */
    private const IGNORED_ATTRIBUTES = ['created_at', 'updated_at'];

    /**
     * @param Stores $storeHelper
     * @param Database $databaseHelper
     * @param \Infrangible\Core\Helper\Product $productHelper
     * @param EntityType $eavEntityTypeHelper
     * @param Data $productFeedHelper
     * @param LoggerInterface $logging
     * @param Type $productType
     * @param CollectionFactory $changeLogCollectionFactory
     * @param Variables $variables
     * @param Models $modelHelper
     * @param Arrays $arrays
     */
    public function __construct(
        protected Stores $storeHelper,
        protected Database $databaseHelper,
        protected \Infrangible\Core\Helper\Product $productHelper,
        protected EntityType $eavEntityTypeHelper,
        protected Data $productFeedHelper,
        protected LoggerInterface $logging,
        protected Type $productType,
        protected CollectionFactory $changeLogCollectionFactory,
        protected Variables $variables,
        protected Models $modelHelper,
        protected Arrays $arrays
    ) {
    }

    /**
     * Adds the entities to the change log; entities in the change log get a new update time.
     *
     * @param int[] $entityIds
     * @param string $entityTypeCode
     *
     * @return void
     * @throws LocalizedException
     */
    public function insertEntityIds(array $entityIds, string $entityTypeCode): void
    {
        if (count($entityIds) === 0) {
            return;
        }

        $date = gmdate('Y-m-d H:i:s');

        $entityTypeId = $this->getEntityTypeId($entityTypeCode);

        $data = array_map(
            static fn(int $entityId): array => [
                'entity_type_id' => $entityTypeId,
                'entity_id'      => $entityId,
                'created_at'     => $date,
                'updated_at'     => $date
            ],
            array_values(array_unique($entityIds))
        );

        $collection = $this->changeLogCollectionFactory->create();

        $collection->getConnection()->insertOnDuplicate($collection->getMainTable(), $data, ['updated_at']);
    }

    /**
     * Returns the IDs of the entities that changed before the date, optionally removing them from the change log.
     *
     * The date is in UTC as Y-m-d H:i:s, by default this second. Entries that change later stay for the next export.
     *
     * @param string $entityTypeCode
     * @param bool $remove
     * @param string|null $before
     *
     * @return int[]
     * @throws LocalizedException
     */
    public function fetchEntityIds(string $entityTypeCode, bool $remove = false, ?string $before = null): array
    {
        $before ??= gmdate('Y-m-d H:i:s');

        $collection = $this->changeLogCollectionFactory->create();

        $connection = $collection->getConnection();

        $select = $connection->select()->from($collection->getMainTable(), ['entity_id']);

        foreach ($this->getWhere($before, $entityTypeCode) as $condition) {
            $select->where($condition);
        }

        $entityIds = array_values(
            array_unique(array_map([$this->variables, 'intValue'], $connection->fetchCol($select)))
        );

        if ($remove) {
            $this->removeEntityIds($before, $entityTypeCode);
        }

        return $entityIds;
    }

    /**
     * Removes the entries that changed before the date (UTC, Y-m-d H:i:s), of all entity types or of the entity type.
     *
     * @param string $before
     * @param string|null $entityTypeCode
     *
     * @return void
     * @throws LocalizedException
     */
    public function removeEntityIds(string $before, ?string $entityTypeCode = null): void
    {
        $collection = $this->changeLogCollectionFactory->create();

        $collection->getConnection()->delete($collection->getMainTable(), $this->getWhere($before, $entityTypeCode));
    }

    /**
     * Returns the conditions of the entries that changed before the date, of all entity types or of the entity type.
     *
     * @param string $before
     * @param string|null $entityTypeCode
     *
     * @return string[]
     * @throws LocalizedException
     */
    protected function getWhere(string $before, ?string $entityTypeCode): array
    {
        $connection = $this->databaseHelper->getDefaultConnection();

        $where = [$connection->quoteInto('updated_at < ?', $before)];

        if ($entityTypeCode !== null) {
            $where[] = $connection->quoteInto('entity_type_id = ?', $this->getEntityTypeId($entityTypeCode));
        }

        return $where;
    }

    /**
     * Returns whether an attribute that changes the price of the product changed.
     *
     * @param Product $product
     *
     * @return bool
     */
    public function isPriceUpdateRequired(Product $product): bool
    {
        return count(array_intersect(self::PRICE_ATTRIBUTES, $this->getChangedAttributeCodes($product))) > 0;
    }

    /**
     * Returns whether an attribute changed that an integration of a store exports in the delta export.
     *
     * @param Product $product
     *
     * @return bool
     */
    public function isAttributeUpdateRequired(Product $product): bool
    {
        $changedAttributeCodes = array_diff($this->getChangedAttributeCodes($product), self::IGNORED_ATTRIBUTES);

        if (count($changedAttributeCodes) === 0) {
            return false;
        }

        foreach ($this->storeHelper->getStores() as $store) {
            try {
                $eavAttributes = $this->productFeedHelper->getAllEavAttributes(
                    Data::MODE_DELTA,
                    $this->variables->intValue($store->getId())
                );

                if (count(array_intersect($changedAttributeCodes, array_keys($eavAttributes))) > 0) {
                    return true;
                }
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        return false;
    }

    /**
     * Returns whether the stock status of the product changed.
     *
     * @param Product $product
     *
     * @return bool
     */
    public function isStockUpdateRequired(Product $product): bool
    {
        $quantityAndStockStatus = $product->getData('quantity_and_stock_status');
        $origQuantityAndStockStatus = $product->getOrigData('quantity_and_stock_status');

        if (is_array($quantityAndStockStatus) && is_array($origQuantityAndStockStatus)) {
            $isInStock = (bool)$this->arrays->getValue($quantityAndStockStatus, 'is_in_stock', false);
            $origIsInStock = (bool)$this->arrays->getValue($origQuantityAndStockStatus, 'is_in_stock', false);

            return $isInStock !== $origIsInStock;
        }

        return false;
    }

    /**
     * Returns the codes of the attributes whose value changed, with category_ids if the categories changed.
     *
     * @param Product $product
     *
     * @return string[]
     */
    public function getChangedAttributeCodes(Product $product): array
    {
        $changedAttributeCodes = array_map(
            [$this->variables, 'stringValue'],
            array_filter($this->modelHelper->getChangedAttributeCodes($product), 'is_scalar')
        );

        $affectedCategoryIds = $product->getData('affected_category_ids');

        if (is_array($affectedCategoryIds) && count($affectedCategoryIds) > 0) {
            $changedAttributeCodes[] = 'category_ids';
        }

        return array_values(array_unique($changedAttributeCodes));
    }

    /**
     * Returns the IDs of the product and, unless it is a composite product, of its parents.
     *
     * The exported data of the parents contains the product.
     *
     * @param Product $product
     *
     * @return int[]
     * @throws Exception
     */
    public function collectProductIds(Product $product): array
    {
        $productId = $this->variables->intValue($product->getId());

        $productIds = [$productId];

        if (! in_array($product->getTypeId(), $this->productType->getCompositeTypes(), true)) {
            $parentIds = $this->productHelper->getParentIds(
                $this->databaseHelper->getDefaultConnection(),
                [$productId],
                false,
                false
            );

            foreach ($parentIds as $parentId) {
                $productIds[] = $this->variables->intValue($parentId);
            }
        }

        return array_values(array_unique($productIds));
    }

    /**
     * Returns the ID of the entity type.
     *
     * @param string $entityTypeCode
     *
     * @return int
     * @throws LocalizedException
     */
    protected function getEntityTypeId(string $entityTypeCode): int
    {
        $entityType = $this->eavEntityTypeHelper->getEntityType($entityTypeCode);

        if ($entityType === null) {
            throw new LocalizedException(__('Unknown entity type: %1', $entityTypeCode));
        }

        return $this->variables->intValue($entityType->getEntityTypeId());
    }
}
