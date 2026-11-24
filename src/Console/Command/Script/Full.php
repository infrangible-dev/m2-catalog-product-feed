<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Console\Command\Script;

use Infrangible\CatalogProductFeed\Traits\Script;
use Infrangible\CatalogProductFeed\Traits\FullTask;
use Infrangible\Task\Console\Command\Script\Task;

/**
 * Runs the full export on the console.
 */
class Full extends Task
{
    use FullTask;
    use Script;

    /**
     * Returns the class of the task.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return \Infrangible\CatalogProductFeed\Task\Full::class;
    }
}
