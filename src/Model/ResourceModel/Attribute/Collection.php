<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute;

use Infrangible\CatalogProductFeed\Model\Attribute;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection of the feed attributes.
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
        $this->_init(Attribute::class, \Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute::class);
    }

    /**
     * Limits the collection to the attributes of the integration.
     *
     * @param string $integrationName
     *
     * @return void
     */
    public function filterByIntegration(string $integrationName): void
    {
        $this->getSelect()->where('integration = ?', $integrationName);
    }

    /**
     * Limits the collection to the attributes of the delta export.
     *
     * @return void
     */
    public function filterDeltaAttributes(): void
    {
        $this->getSelect()->where('use_for_delta = ?', 1);
    }

    /**
     * Limits the collection to the attributes with a product attribute as source.
     *
     * @return void
     */
    public function filterEavAttributes(): void
    {
        $this->getSelect()->where('eav_attribute_id > 0');
    }

    /**
     * Sorts the collection by the position of the attributes in the feed.
     *
     * @return void
     */
    public function orderByPosition(): void
    {
        $this->getSelect()->order('position ASC');
    }
}
