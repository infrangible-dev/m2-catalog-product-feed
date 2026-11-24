<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Traits;

/**
 * The task of the full export.
 */
trait FullTask
{
    /**
     * Returns the name of the task.
     *
     * @return string
     */
    protected function getTaskName(): string
    {
        return 'catalog_product_feed_full';
    }
}
