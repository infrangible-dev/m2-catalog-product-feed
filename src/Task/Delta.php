<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Task;

use Exception;
use Infrangible\CatalogProductFeed\Helper\Data;
use Magento\Catalog\Model\Product;

/**
 * Exports the products of the change log with the integrations used for the delta export. The exported entries are
 * removed from the change log after the export (in test mode, they are kept).
 */
class Delta extends Base
{
    /**
     * Exports the changed products.
     *
     * @return bool
     * @throws Exception
     */
    protected function runTask(): bool
    {
        $this->logging->info('Starting product feed delta export');

        $before = gmdate('Y-m-d H:i:s');

        $entityIds = $this->productFeedChangeLogHelper->fetchEntityIds(Product::ENTITY, false, $before);

        if (count($entityIds) > 0) {
            $this->reindexProducts(
                Data::MODE_DELTA,
                $this->getStoreCode(),
                $entityIds,
                $this->getIntegrationNames()
            );

            if (! $this->isTest()) {
                $this->productFeedChangeLogHelper->removeEntityIds($before, Product::ENTITY);
            }
        }

        $this->logging->info('Finished product feed delta export');

        return true;
    }
}
