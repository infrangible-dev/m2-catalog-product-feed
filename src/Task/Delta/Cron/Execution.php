<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Task\Delta\Cron;

use Infrangible\CatalogProductFeed\Traits\DeltaTask;
use Infrangible\Task\Cron\Execution\Base;

/**
 * Stores the configured cron expression of the delta export as schedule of its cron job.
 */
class Execution extends Base
{
    use DeltaTask;
}
