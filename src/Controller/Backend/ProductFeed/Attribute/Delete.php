<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Controller\Backend\ProductFeed\Attribute;

use Infrangible\CatalogProductFeed\Traits\Attribute;

/**
 * Deletes a feed attribute: integrations extend it with their ACL resource and menu.
 */
abstract class Delete extends \Infrangible\BackendWidget\Controller\Backend\Object\Delete
{
    use Attribute;

    /**
     * Returns the message after deleting an attribute.
     *
     * @return string
     */
    protected function getObjectDeletedMessage(): string
    {
        return __('The attribute has been deleted.')->render();
    }
}
