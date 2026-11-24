<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Model\Integration;

use Infrangible\CatalogProductFeed\Model\Integration\Simple;

/**
 * An integration that records the exported values, as type => list of values.
 */
class RecordingIntegration extends Simple
{
    /**
     * @var array
     * @phpstan-var array<string, array<int, array<string, mixed>>>
     */
    public array $exported = [];

    /**
     * Records the values of a product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    protected function exportProduct(string $mode, int $storeId, array $exportData): void
    {
        $this->exported[ 'product' ][] = $exportData;
    }

    /**
     * Records the values of a child.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    protected function exportChild(string $mode, int $storeId, array $exportData): void
    {
        $this->exported[ 'child' ][] = $exportData;
    }

    /**
     * Records the values of a bundled product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    protected function exportBundled(string $mode, int $storeId, array $exportData): void
    {
        $this->exported[ 'bundled' ][] = $exportData;
    }

    /**
     * Records the values of a grouped product.
     *
     * @param string $mode
     * @param int $storeId
     * @param array $exportData
     * @phpstan-param array<string, mixed> $exportData
     *
     * @return void
     */
    protected function exportGrouped(string $mode, int $storeId, array $exportData): void
    {
        $this->exported[ 'grouped' ][] = $exportData;
    }

    /**
     * Returns whether the integration is enabled: always.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId): bool
    {
        return true;
    }

    /**
     * Returns whether the integration exports changes: always.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function useForDelta(int $storeId): bool
    {
        return true;
    }

    /**
     * Returns the attribute conditions: none.
     *
     * @return string[]
     */
    public function getAttributeConditions(): array
    {
        return [];
    }

    /**
     * Returns whether the integration is scheduled: never.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isScheduled(int $storeId): bool
    {
        return false;
    }

    /**
     * Returns the schedule: none.
     *
     * @param int $storeId
     *
     * @return string[]
     */
    public function getScheduleExpression(int $storeId): array
    {
        return [];
    }
}
