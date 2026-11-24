<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Task;

use Exception;
use Infrangible\CatalogProductFeed\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

/**
 * Exports all products with the integrations whose schedule was due since the last run, or with all integrations
 * with --force. The time of the last successful run is stored in var/task/<task name>.run.
 */
class Full extends Base
{
    /** @var string */
    private string $taskRunTimeFile = '';

    /** @var int|null start of the export */
    private ?int $timeStamp = null;

    /**
     * Prepares the directory of the file with the time of the last run.
     *
     * @return void
     * @throws FileSystemException
     */
    protected function prepare(): void
    {
        parent::prepare();

        $this->timeStamp = null;

        $this->taskRunTimeFile = sprintf(
            '%s/task/%s.run',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->getTaskName()
        );

        $this->file->createDirectory($this->file->getParentDirectory($this->taskRunTimeFile));
    }

    /**
     * Exports the products.
     *
     * @return bool
     * @throws Exception
     */
    protected function runTask(): bool
    {
        $this->logging->info('Starting product feed full export');

        $this->reindexAll(
            Data::MODE_FULL,
            $this->getStoreCode(),
            $this->getIntegrationNames(),
            $this->getLastRunTime()
        );

        $this->logging->info('Finished product feed full export');

        return true;
    }

    /**
     * Returns the time of the last run, the current time before the first run, or null with --force.
     *
     * @return int|null
     * @throws FileSystemException
     */
    protected function getLastRunTime(): ?int
    {
        if ($this->isForce()) {
            return null;
        }

        if ($this->file->isExists($this->taskRunTimeFile)) {
            $lastRunTime = trim($this->file->fileGetContents($this->taskRunTimeFile));

            if (is_numeric($lastRunTime)) {
                return (int)$lastRunTime;
            }
        }

        return time();
    }

    /**
     * Exports all products.
     *
     * After an export with all integrations that ignored their schedules, the change log entries before the export
     * are removed: the export contains their changes.
     *
     * @param string $useMode
     * @param string|null $storeCode
     * @param string[] $integrationNames
     * @param int|null $lastRunTime
     *
     * @return void
     * @throws Exception
     */
    public function reindexAll(
        string $useMode,
        ?string $storeCode = null,
        array $integrationNames = [],
        ?int $lastRunTime = null
    ): void {
        $this->timeStamp = time();

        $this->reindexProducts($useMode, $storeCode, [], $integrationNames, $lastRunTime);

        if (count($integrationNames) === 0 && $lastRunTime === null) {
            $this->productFeedChangeLogHelper->removeEntityIds(gmdate('Y-m-d H:i:s', $this->timeStamp));
        }
    }

    /**
     * Stores the start of a successful export as time of the last run.
     *
     * @param bool $success
     *
     * @return void
     * @throws FileSystemException
     */
    protected function dismantle(bool $success): void
    {
        parent::dismantle($success);

        if ($success && $this->timeStamp !== null && $this->taskRunTimeFile !== '') {
            $this->file->filePutContents($this->taskRunTimeFile, (string)$this->timeStamp);
        }
    }
}
