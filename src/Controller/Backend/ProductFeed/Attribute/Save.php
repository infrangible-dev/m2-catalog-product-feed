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
 * Saves a feed attribute: integrations extend it with their ACL resource and menu.
 */
abstract class Save extends \Infrangible\BackendWidget\Controller\Backend\Object\Save
{
    use Attribute;

    /**
     * Returns the message after creating an attribute.
     *
     * @return string
     */
    protected function getObjectCreatedMessage(): string
    {
        return __('The attribute has been created.')->render();
    }

    /**
     * Returns the message after updating an attribute.
     *
     * @return string
     */
    protected function getObjectUpdatedMessage(): string
    {
        return __('The attribute has been updated.')->render();
    }
}
