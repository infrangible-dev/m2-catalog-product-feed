<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Traits;

use Infrangible\CatalogProductFeed\Task\Base;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Passes the options --integration and --force of the commands to the task.
 */
trait Script
{
    /**
     * Prepares the task with the options of the command.
     *
     * @param \Infrangible\Task\Task\Base $task
     * @param InputInterface $input
     *
     * @return void
     */
    protected function prepareTask(\Infrangible\Task\Task\Base $task, InputInterface $input): void
    {
        parent::prepareTask($task, $input);

        if (! $task instanceof Base) {
            return;
        }

        $integration = $input->hasOption('integration') ? $input->getOption('integration') : null;

        if (is_string($integration) && trim($integration) !== '') {
            $task->setIntegrationNames(array_values(array_filter(array_map('trim', explode(',', $integration)))));
        }

        if ($input->hasOption('force') && $input->getOption('force')) {
            $task->setForce(true);
        }
    }
}
