<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Traits;

/**
 * The feed attributes in the admin controllers of the integrations.
 */
trait Attribute
{
    /**
     * Returns the name of the object.
     *
     * @return string
     */
    protected function getObjectName(): string
    {
        return 'Attribute';
    }

    /**
     * Returns the request parameter with the object ID.
     *
     * @return string|null
     */
    protected function getObjectField(): ?string
    {
        return 'attribute_id';
    }

    /**
     * Returns whether attributes can be added.
     *
     * @return bool
     */
    protected function allowAdd(): bool
    {
        return true;
    }

    /**
     * Returns whether attributes can be edited.
     *
     * @return bool
     */
    protected function allowEdit(): bool
    {
        return true;
    }

    /**
     * Returns whether attributes can be viewed without editing.
     *
     * @return bool
     */
    protected function allowView(): bool
    {
        return false;
    }

    /**
     * Returns whether attributes can be deleted.
     *
     * @return bool
     */
    protected function allowDelete(): bool
    {
        return true;
    }

    /**
     * Returns the message for an attribute ID that does not exist, with %s for the ID.
     *
     * @return string
     */
    protected function getObjectNotFoundMessage(): string
    {
        return __('The attribute with id: %s does not exist.')->render();
    }
}
