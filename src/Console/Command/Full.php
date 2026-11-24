<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Console\Command;

use Infrangible\CatalogProductFeed\Traits\Command;
use Infrangible\CatalogProductFeed\Traits\FullTask;
use Infrangible\Task\Console\Command\Task;

/**
 * The command task:catalog_product_feed_full.
 */
class Full extends Task
{
    use FullTask;
    use Command;

    /**
     * Returns the class of the script that runs the task.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return Script\Full::class;
    }

    /**
     * Returns the description of the command.
     *
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Process all product feeds';
    }

    /**
     * Returns whether the command has the option --force.
     *
     * @return bool
     */
    protected function hasForceOption(): bool
    {
        return true;
    }
}
