<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Traits;

use Symfony\Component\Console\Input\InputOption;

/**
 * The option --integration of the commands, and --force of the full export.
 */
trait Command
{
    /**
     * Returns the options of the command.
     *
     * @return InputOption[]
     */
    protected function getCommandDefinition(): array
    {
        $commandDefinition = parent::getCommandDefinition();

        // optional: the command of m2-core requires the options with a required value
        $commandDefinition[] = new InputOption(
            'integration',
            null,
            InputOption::VALUE_OPTIONAL,
            'Names of the integrations separated by comma, all if empty'
        );

        if ($this->hasForceOption()) {
            $commandDefinition[] = new InputOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Ignore the schedules of the integrations'
            );
        }

        return $commandDefinition;
    }

    /**
     * Returns whether the command has the option --force.
     *
     * @return bool
     */
    abstract protected function hasForceOption(): bool;
}
