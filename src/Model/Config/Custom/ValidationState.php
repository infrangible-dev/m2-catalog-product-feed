<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Config\Custom;

use Magento\Framework\Config\ValidationStateInterface;

/**
 * The files etc/catalog_product_feed_attribute.xml are not validated, they have no schema.
 */
class ValidationState implements ValidationStateInterface
{
    /**
     * Returns whether the files are validated: never.
     *
     * @return bool
     */
    public function isValidationRequired(): bool
    {
        return false;
    }
}
