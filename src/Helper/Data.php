<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Helper;

use Cron\CronExpression;
use DateTime;
use Exception;
use Infrangible\CatalogProductFeed\Model\Attribute;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;

/**
 * The integrations of the stores, and the product attributes they export.
 *
 * Integrations are configured as infrangible_catalogproductfeed/integration/<name> = <class implementing IIntegration>.
 */
class Data
{
    /** Mode of the export of the changed products. */
    public const MODE_DELTA = 'delta';

    /** Mode of the export of all products. */
    public const MODE_FULL = 'full';

    /** @var IIntegration[] the created integrations by name */
    private array $integrations = [];

    /**
     * @param Stores $storeHelper
     * @param Export $catalogExportHelper
     * @param \Infrangible\Core\Helper\Attribute $attributeHelper
     * @param LoggerInterface $logging
     * @param UniversalFactory $universalFactory
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        protected Stores $storeHelper,
        protected Export $catalogExportHelper,
        protected \Infrangible\Core\Helper\Attribute $attributeHelper,
        protected LoggerInterface $logging,
        protected UniversalFactory $universalFactory,
        protected CollectionFactory $attributeCollectionFactory
    ) {
    }

    /**
     * Returns the names of the integrations configured in the store.
     *
     * @param int $storeId
     *
     * @return string[]
     */
    public function getAllIntegrationNames(int $storeId): array
    {
        return array_map('strval', array_keys($this->getIntegrationsConfig($storeId)));
    }

    /**
     * Returns the integrations of the store that export in this run.
     *
     * These are enabled, used in the mode and, if scheduled, due since the last run.
     *
     * @param string $useMode
     * @param int $storeId
     * @param string[] $integrationNames the integrations to use
     * @param int|null $lastRunTime time of the last run, null to ignore the schedules
     *
     * @return IIntegration[] by name
     * @throws Exception
     */
    public function getIntegrations(
        string $useMode,
        int $storeId,
        array $integrationNames,
        ?int $lastRunTime = null
    ): array {
        $integrationInstances = [];

        foreach ($this->getIntegrationsConfig($storeId) as $integrationName => $integrationClass) {
            $integrationName = (string)$integrationName;

            if (! in_array($integrationName, $integrationNames, true)) {
                continue;
            }

            $integration = $this->getIntegration($integrationName, $integrationClass);

            if (! $integration->isEnabled($storeId)) {
                continue;
            }

            if ($useMode === static::MODE_DELTA && ! $integration->useForDelta($storeId)) {
                continue;
            }

            if ($lastRunTime !== null && $integration->isScheduled($storeId) &&
                ! $this->isDue($integration->getScheduleExpression($storeId), $lastRunTime)) {
                continue;
            }

            $integrationInstances[ $integrationName ] = $integration;
        }

        return $integrationInstances;
    }

    /**
     * Returns whether one of the cron expressions had a run time since the last run.
     *
     * @param string[] $scheduleExpressions
     * @param int $lastRunTime
     *
     * @return bool
     */
    public function isDue(array $scheduleExpressions, int $lastRunTime): bool
    {
        $lastRun = DateTime::createFromFormat('U', (string)$lastRunTime);
        $now = DateTime::createFromFormat('U', (string)time());

        if ($lastRun === false || $now === false) {
            return false;
        }

        foreach ($scheduleExpressions as $scheduleExpression) {
            if (trim($scheduleExpression) === '') {
                continue;
            }

            try {
                $cronExpression = new CronExpression($scheduleExpression);

                if ($cronExpression->getNextRunDate($lastRun, 0, true) <= $now) {
                    return true;
                }
            } catch (Exception $exception) {
                $this->logging->error($exception);
            }
        }

        return false;
    }

    /**
     * Returns the codes of the product attributes the integrations need and the feed attributes use.
     *
     * @param IIntegration[] $integrations
     *
     * @return string[]
     * @throws Exception
     */
    public function getAdditionalEavAttributeCodes(array $integrations): array
    {
        return array_values(
            array_unique(
                array_merge(
                    $this->getRequiredEavAttributeCodes($integrations),
                    $this->getConfiguredEavAttributeCodes()
                )
            )
        );
    }

    /**
     * Returns the codes of the product attributes the integrations need.
     *
     * @param IIntegration[] $integrations
     *
     * @return string[]
     */
    public function getRequiredEavAttributeCodes(array $integrations): array
    {
        $requiredEavAttributeCodes = [];

        foreach ($integrations as $integration) {
            foreach ($integration->getRequiredEavAttributeCodes() as $requiredEavAttributeCode) {
                $requiredEavAttributeCodes[] = $requiredEavAttributeCode;
            }
        }

        return array_values(array_unique($requiredEavAttributeCodes));
    }

    /**
     * Returns the conditions of the integrations, which product attributes are exported.
     *
     * @param IIntegration[] $integrations
     *
     * @return string[]
     */
    public function getAttributeConditions(array $integrations): array
    {
        $attributeConditions = [];

        foreach ($integrations as $integration) {
            foreach ($integration->getAttributeConditions() as $attributeCondition) {
                $attributeConditions[] = $attributeCondition;
            }
        }

        return array_values(array_unique($attributeConditions));
    }

    /**
     * Returns the codes of the product attributes that the feed attributes of all integrations use as source.
     *
     * @return string[]
     * @throws Exception
     */
    public function getConfiguredEavAttributeCodes(): array
    {
        $configuredEavAttributeCodes = [];

        $attributeCollection = $this->attributeCollectionFactory->create();

        $attributeCollection->filterEavAttributes();

        foreach ($attributeCollection as $attribute) {
            if (! $attribute instanceof Attribute) {
                continue;
            }

            $eavAttributeId = $attribute->getEavAttributeId();

            if ($eavAttributeId === null) {
                continue;
            }

            $eavAttribute = $this->attributeHelper->getAttribute(Product::ENTITY, (string)$eavAttributeId);

            $configuredEavAttributeCodes[] = (string)$eavAttribute->getAttributeCode();
        }

        return array_values(array_unique($configuredEavAttributeCodes));
    }

    /**
     * Returns the product attributes that the integrations of the store export in the mode.
     *
     * @param string $useMode
     * @param int $storeId
     *
     * @return \Magento\Eav\Model\Entity\Attribute[] by code
     * @throws Exception
     */
    public function getAllEavAttributes(string $useMode, int $storeId): array
    {
        $integrations = $this->getIntegrations($useMode, $storeId, $this->getAllIntegrationNames($storeId));

        return $this->catalogExportHelper->getExportableAttributes(
            [],
            $this->getAdditionalEavAttributeCodes($integrations)
        );
    }

    /**
     * Returns the configured integrations of the store as name => class.
     *
     * @param int $storeId
     *
     * @return array
     * @phpstan-return array<int|string, string>
     */
    protected function getIntegrationsConfig(int $storeId): array
    {
        $integrationsConfig = $this->storeHelper->getStoreConfig(
            'infrangible_catalogproductfeed/integration',
            [],
            false,
            $storeId
        );

        return is_array($integrationsConfig) ?
            array_filter($integrationsConfig, static fn(mixed $class): bool => is_string($class) && $class !== '') :
            [];
    }

    /**
     * Returns the integration with the name, which is created once.
     *
     * @param string $integrationName
     * @param string $integrationClass
     *
     * @return IIntegration
     * @throws Exception
     */
    protected function getIntegration(string $integrationName, string $integrationClass): IIntegration
    {
        if (! array_key_exists($integrationName, $this->integrations)) {
            $integration = $this->universalFactory->create($integrationClass);

            if (! $integration instanceof IIntegration) {
                throw new LocalizedException(
                    __(
                        'Integration class: %1 of integration: %2 does not implement: %3',
                        $integrationClass,
                        $integrationName,
                        IIntegration::class
                    )
                );
            }

            $this->integrations[ $integrationName ] = $integration;
        }

        return $this->integrations[ $integrationName ];
    }
}
