<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Plugin\Catalog\Model;

use Exception;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;

/**
 * Adds saved products to the change log of the delta export, if their exported data changed.
 */
class Product
{
    /**
     * @param ChangeLog $changeLogHelper
     */
    public function __construct(protected ChangeLog $changeLogHelper)
    {
    }

    /**
     * Adds the product and its parents to the change log if its price changed.
     *
     * @param \Magento\Catalog\Model\Product $subject
     * @param mixed $result
     *
     * @return mixed
     * @throws Exception
     */
    public function afterPriceReindexCallback(\Magento\Catalog\Model\Product $subject, mixed $result): mixed
    {
        if ($this->changeLogHelper->isPriceUpdateRequired($subject)) {
            $this->addProduct($subject);
        }

        return $result;
    }

    /**
     * Adds the product and its parents to the change log if an exported attribute or the stock status changed.
     *
     * Price changes are added by afterPriceReindexCallback().
     *
     * @param \Magento\Catalog\Model\Product $subject
     * @param mixed $result
     *
     * @return mixed
     * @throws Exception
     */
    public function afterReindex(\Magento\Catalog\Model\Product $subject, mixed $result): mixed
    {
        if (! $this->changeLogHelper->isPriceUpdateRequired($subject) &&
            ($this->changeLogHelper->isStockUpdateRequired($subject) ||
                $this->changeLogHelper->isAttributeUpdateRequired($subject))) {
            $this->addProduct($subject);
        }

        return $result;
    }

    /**
     * Adds the product and its parents to the change log.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return void
     * @throws Exception
     */
    private function addProduct(\Magento\Catalog\Model\Product $product): void
    {
        $this->changeLogHelper->insertEntityIds(
            $this->changeLogHelper->collectProductIds($product),
            \Magento\Catalog\Model\Product::ENTITY
        );
    }
}
