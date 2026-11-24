<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Config\Custom;

use Magento\Framework\Config\SchemaLocatorInterface;

/**
 * The files etc/catalog_product_feed_attribute.xml have no schema.
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * Returns the schema of the merged files: none.
     *
     * @return string|null
     */
    public function getSchema(): ?string
    {
        return null;
    }

    /**
     * Returns the schema of the single files: none.
     *
     * @return string|null
     */
    public function getPerFileSchema(): ?string
    {
        return null;
    }
}
