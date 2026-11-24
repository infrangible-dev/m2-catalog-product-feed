<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Helper;

use Exception;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Validator\UniversalFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;

/**
 * Tests the selection of the integrations of a run and their schedules.
 */
class DataTest extends TestCase
{
    /**
     * Returns the helper with the integrations configured as name => class and the created objects by class.
     *
     * @param array $integrationsConfig
     * @param array $objects
     * @param LoggerInterface|null $logging
     * @phpstan-param array<string, string> $integrationsConfig
     * @phpstan-param array<string, object> $objects
     *
     * @return Data
     */
    private function createHelper(
        array $integrationsConfig = [],
        array $objects = [],
        ?LoggerInterface $logging = null
    ): Data {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfig')->willReturn($integrationsConfig);

        $universalFactory = $this->createStub(UniversalFactory::class);
        $universalFactory->method('create')->willReturnCallback(
            fn(string $className): object => $objects[ $className ]
        );

        return new Data(
            $storeHelper,
            $this->createStub(Export::class),
            $this->createStub(Attribute::class),
            $logging ?? new NullLogger(),
            $universalFactory,
            $this->createStub(CollectionFactory::class)
        );
    }

    /**
     * Returns an integration.
     *
     * @param bool $enabled
     * @param bool $useForDelta
     * @param string[] $scheduleExpressions the schedule, none if empty
     *
     * @return IIntegration
     */
    private function createIntegration(
        bool $enabled,
        bool $useForDelta = true,
        array $scheduleExpressions = []
    ): IIntegration {
        $integration = $this->createStub(IIntegration::class);
        $integration->method('isEnabled')->willReturn($enabled);
        $integration->method('useForDelta')->willReturn($useForDelta);
        $integration->method('isScheduled')->willReturn(count($scheduleExpressions) > 0);
        $integration->method('getScheduleExpression')->willReturn($scheduleExpressions);

        return $integration;
    }

    /**
     * Returns a cron expression that runs in about 30 minutes, every hour.
     *
     * @return string
     */
    private function getExpressionInHalfAnHour(): string
    {
        return sprintf('%d * * * *', ((int)gmdate('i') + 30) % 60);
    }

    /**
     * An integration is due if its schedule had a run time since the last run.
     *
     * @return void
     */
    public function testIsDueWithRunTimeSinceLastRun(): void
    {
        $helper = $this->createHelper();

        $this->assertTrue($helper->isDue(['* * * * *'], time() - 120));
        $this->assertFalse($helper->isDue([$this->getExpressionInHalfAnHour()], time() - 60));
        $this->assertTrue($helper->isDue(['', $this->getExpressionInHalfAnHour(), '* * * * *'], time() - 120));
    }

    /**
     * Invalid cron expressions are logged and never due.
     *
     * @return void
     */
    public function testIsDueLogsInvalidExpressions(): void
    {
        $logging = $this->createMock(LoggerInterface::class);
        $logging->expects($this->once())->method('error');

        $this->assertFalse($this->createHelper([], [], $logging)->isDue(['not a cron expression'], time() - 120));
    }

    /**
     * The integrations of a run are the requested ones that are enabled and used in the mode.
     *
     * @return void
     * @throws Exception
     */
    public function testGetIntegrationsFiltersTheIntegrationsOfTheRun(): void
    {
        $enabled = $this->createIntegration(true);
        $disabled = $this->createIntegration(false);
        $fullOnly = $this->createIntegration(true, false);
        $notRequested = $this->createIntegration(true);

        $helper = $this->createHelper(
            ['enabled' => 'Enabled', 'disabled' => 'Disabled', 'full_only' => 'FullOnly', 'other' => 'Other'],
            ['Enabled' => $enabled, 'Disabled' => $disabled, 'FullOnly' => $fullOnly, 'Other' => $notRequested]
        );

        $names = ['enabled', 'disabled', 'full_only'];

        $this->assertSame(['enabled' => $enabled], $helper->getIntegrations(Data::MODE_DELTA, 1, $names));
        $this->assertSame(
            ['enabled' => $enabled, 'full_only' => $fullOnly],
            $helper->getIntegrations(Data::MODE_FULL, 1, $names)
        );
        $this->assertSame(['enabled', 'disabled', 'full_only', 'other'], $helper->getAllIntegrationNames(1));
    }

    /**
     * Scheduled integrations are used if their schedule was due since the last run, or if schedules are ignored.
     *
     * @return void
     * @throws Exception
     */
    public function testGetIntegrationsFollowsTheSchedules(): void
    {
        $due = $this->createIntegration(true, true, ['* * * * *']);
        $notDue = $this->createIntegration(true, true, [$this->getExpressionInHalfAnHour()]);

        $helper = $this->createHelper(['due' => 'Due', 'not_due' => 'NotDue'], ['Due' => $due, 'NotDue' => $notDue]);

        $this->assertSame(
            ['due' => $due],
            $helper->getIntegrations(Data::MODE_FULL, 1, ['due', 'not_due'], time() - 120)
        );
        $this->assertSame(
            ['due' => $due, 'not_due' => $notDue],
            $helper->getIntegrations(Data::MODE_FULL, 1, ['due', 'not_due'])
        );
    }

    /**
     * Configured classes that are no integrations fail.
     *
     * @return void
     * @throws Exception
     */
    public function testGetIntegrationsRejectsOtherClasses(): void
    {
        $helper = $this->createHelper(['broken' => 'Broken'], ['Broken' => new stdClass()]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('does not implement');

        $helper->getIntegrations(Data::MODE_FULL, 1, ['broken']);
    }

    /**
     * The attribute conditions and required attributes of the integrations are merged without duplicates.
     *
     * @return void
     */
    public function testMergesTheRequirementsOfTheIntegrations(): void
    {
        $first = $this->createStub(IIntegration::class);
        $first->method('getAttributeConditions')->willReturn(['is_searchable', 'is_filterable']);
        $first->method('getRequiredEavAttributeCodes')->willReturn(['name', 'price']);

        $second = $this->createStub(IIntegration::class);
        $second->method('getAttributeConditions')->willReturn(['is_filterable']);
        $second->method('getRequiredEavAttributeCodes')->willReturn(['price', 'image']);

        $helper = $this->createHelper();

        $this->assertSame(['is_searchable', 'is_filterable'], $helper->getAttributeConditions([$first, $second]));
        $this->assertSame(['name', 'price', 'image'], $helper->getRequiredEavAttributeCodes([$first, $second]));
    }
}
