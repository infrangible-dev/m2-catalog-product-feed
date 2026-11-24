<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Indexer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * An entry of the change log: an entity whose data changed since the last delta export.
 */
class Changelog extends AbstractModel
{
    /** @var string */
    protected $_eventPrefix = 'infrangible_catalogproductfeed_indexer_changelog';

    /**
     * Sets the resource model.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _construct(): void
    {
        $this->_init(\Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog::class);
    }

    /**
     * Sets the creation and update time.
     *
     * @return $this
     */
    public function beforeSave(): static
    {
        if ($this->isObjectNew()) {
            $this->setData('created_at', gmdate('Y-m-d H:i:s'));
        }

        $this->setData('updated_at', gmdate('Y-m-d H:i:s'));

        parent::beforeSave();

        return $this;
    }
}
