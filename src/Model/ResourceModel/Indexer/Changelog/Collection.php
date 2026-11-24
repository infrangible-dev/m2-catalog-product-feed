<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog;

use Infrangible\CatalogProductFeed\Model\Indexer\Changelog;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection of the change log entries.
 */
class Collection extends AbstractCollection
{
    /**
     * Sets the model and the resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            Changelog::class,
            \Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog::class
        );
    }

    /**
     * Limits the collection to the entries of the entity type.
     *
     * @param int $entityTypeId
     *
     * @return void
     */
    public function addEntityTypeIdFilter(int $entityTypeId): void
    {
        $this->addFieldToFilter('entity_type_id', ['eq' => $entityTypeId]);
    }
}
