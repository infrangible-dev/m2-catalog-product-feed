<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model;

use Magento\Framework\DataObject;

/**
 * The data of a product before its export, passed to the observers of the event
 * infrangible_catalogproductfeed_export_validation. An observer skips the product with setResult(false).
 *
 * @method string getMode()
 * @method $this setMode(string $mode)
 * @method int getStoreId()
 * @method $this setStoreId(int $storeId)
 * @method array<string, mixed> getProductData()
 * @method $this setProductData(array $productData)
 * @method array getCategoryPaths()
 * @method $this setCategoryPaths(array $categoryPaths)
 * @method array<int|string, mixed> getUrlRewrites()
 * @method $this setUrlRewrites(array $urlRewrites)
 * @method array<int|string, mixed> getGalleryImages()
 * @method $this setGalleryImages(array $galleryImages)
 * @method array<string, mixed> getIndexedPrices()
 * @method $this setIndexedPrices(array $indexedPrices)
 * @method array<string, mixed> getStockItem()
 * @method $this setStockItem(array $stockItem)
 * @method array<string, mixed> getReviewSummary()
 * @method $this setReviewSummary(array $reviewSummary)
 * @method array getChildren()
 * @method $this setChildren(array $children)
 * @method array getBundled()
 * @method $this setBundled(array $bundled)
 * @method array getGrouped()
 * @method $this setGrouped(array $grouped)
 * @method $this setResult(bool $result)
 */
class ExportValidation extends DataObject
{
    /**
     * Returns whether the product is exported, also if an observer set the result with setData().
     *
     * @return bool
     */
    public function getResult(): bool
    {
        return (bool)$this->getData('result');
    }
}
