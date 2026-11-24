<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model of the feed attributes.
 */
class Attribute extends AbstractDb
{
    /**
     * Sets the table and its primary key.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('catalog_product_feed_attribute', 'attribute_id');
    }
}
