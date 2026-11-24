<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Task;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\CatalogIndexerFlat\Helper\Data as FlatHelper;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\ExportValidation;
use Infrangible\CatalogProductFeed\Model\ExportValidationFactory;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\CollectionFactory;
use Infrangible\CatalogProductFeed\Task\Delta;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\RunFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the export of the products of the stores with the integrations.
 */
class DeltaTest extends TestCase
{
    /**
     * Returns a store with the ID as string, like loaded from the database.
     *
     * @param int $storeId
     *
     * @return Store
     */
    private function createStore(int $storeId): Store
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn((string)$storeId);

        return $store;
    }

    /**
     * Returns the task with the stores 1 and 2 and the integration, which exports a product with a child per store.
     *
     * @param IIntegration $integration
     * @param FlatHelper $flatHelper
     * @param Export|null $exportHelper
     *
     * @return Delta
     */
    private function createTask(
        IIntegration $integration,
        FlatHelper $flatHelper,
        ?Export $exportHelper = null
    ): Delta {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStore')->willReturnCallback(
            fn(string $storeCode): Store => $this->createStore($storeCode === 'admin' ? 0 : 2)
        );
        $storeHelper->method('getStores')->willReturn([1 => $this->createStore(1), 2 => $this->createStore(2)]);

        $database = $this->createStub(Database::class);
        $database->method('getDefaultConnection')->willReturn($this->createStub(AdapterInterface::class));

        if ($exportHelper === null) {
            $exportHelper = $this->createStub(Export::class);
            // one block of products per store
            $exportHelper->method('getExportableProductIds')->willReturnOnConsecutiveCalls([10], [], [10], []);
            $exportHelper->method('getProductsData')->willReturn(
                [10 => ['sku' => 'MB01', 'children' => [11 => ['sku' => 'MB01-S']], 'stock_item' => []]]
            );
        }

        $productFeedHelper = $this->createStub(Data::class);
        $productFeedHelper->method('getAllIntegrationNames')->willReturn(['test']);
        $productFeedHelper->method('getIntegrations')->willReturn(['test' => $integration]);
        $productFeedHelper->method('getAttributeConditions')->willReturn([]);
        $productFeedHelper->method('getAdditionalEavAttributeCodes')->willReturn([]);

        $attributeCollectionFactory = $this->createStub(CollectionFactory::class);
        $attributeCollectionFactory->method('create')->willReturn($this->createStub(Collection::class));

        $exportValidationFactory = $this->createStub(ExportValidationFactory::class);
        $exportValidationFactory->method('create')->willReturnCallback(
            fn(): ExportValidation => new ExportValidation()
        );

        return new Delta(
            $this->createStub(File::class),
            $this->createStub(Registry::class),
            $this->createStub(\Infrangible\Task\Helper\Data::class),
            new NullLogger(),
            $this->createStub(DirectoryList::class),
            $this->createStub(RunFactory::class),
            $this->createStub(RunResourceFactory::class),
            $this->createStub(MailFactory::class),
            new Variables(),
            $storeHelper,
            $database,
            $exportHelper,
            $productFeedHelper,
            $attributeCollectionFactory,
            $flatHelper,
            $exportValidationFactory,
            $this->createStub(ManagerInterface::class),
            $this->createStub(ChangeLog::class)
        );
    }

    /**
     * With the admin store, the products of all stores are exported; the integration starts and finishes once per
     * run, and the flat product index is disabled while a store is exported.
     *
     * @return void
     * @throws Exception
     */
    public function testExportsAllStoresAndStartsTheIntegrationsOnce(): void
    {
        $integration = $this->createMock(IIntegration::class);
        $integration->expects($this->once())->method('start');
        $integration->expects($this->once())->method('finish');
        $integration->expects($this->exactly(2))->method('startStore');
        $integration->expects($this->exactly(2))->method('finishStore');
        $integration->expects($this->exactly(2))->method('exportProductData');
        $integration->expects($this->exactly(2))->method('exportChildData');

        $flatHelper = $this->createMock(FlatHelper::class);
        $flatHelper->expects($this->exactly(2))->method('disableProductFlatIndexer');
        $flatHelper->expects($this->exactly(2))->method('removeDisableProductFlatIndexer');

        $task = $this->createTask($integration, $flatHelper);

        $task->reindexProducts(Data::MODE_DELTA, 'admin', [10]);

        $this->assertFalse($task->isEmptyRun());
    }

    /**
     * A failing export of a store enables the flat product index again and finishes the store.
     *
     * @return void
     * @throws Exception
     */
    public function testEnablesTheFlatIndexAfterAFailure(): void
    {
        $integration = $this->createMock(IIntegration::class);
        $integration->expects($this->once())->method('finishStore');
        $integration->expects($this->never())->method('exportProductData');

        $flatHelper = $this->createMock(FlatHelper::class);
        $flatHelper->expects($this->once())->method('disableProductFlatIndexer');
        $flatHelper->expects($this->once())->method('removeDisableProductFlatIndexer');

        $exportHelper = $this->createStub(Export::class);
        $exportHelper->method('getExportableProductIds')->willThrowException(new Exception('Database is gone'));

        $task = $this->createTask($integration, $flatHelper, $exportHelper);

        $task->reindexProducts(Data::MODE_DELTA, 'default', [10]);
    }
}
