<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Task\Delta;

use Infrangible\CatalogProductFeed\Task\Delta;
use Infrangible\CatalogProductFeed\Traits\DeltaTask;
use Infrangible\Task\Cron\Base;

/**
 * The cron job of the delta export.
 */
class Cron extends Base
{
    use DeltaTask;

    /**
     * Returns the class of the task.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return Delta::class;
    }
}
