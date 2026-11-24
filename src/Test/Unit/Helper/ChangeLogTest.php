<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Indexer\Changelog\CollectionFactory;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\EntityType;
use Infrangible\Core\Helper\Models;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests which product changes go into the change log, and which products are added for a changed product.
 */
class ChangeLogTest extends TestCase
{
    /**
     * Returns the helper, with the changed attributes of products and the parents of every product.
     *
     * @param string[] $changedAttributeCodes
     * @param int[] $parentIds
     *
     * @return ChangeLog
     */
    private function createHelper(array $changedAttributeCodes = [], array $parentIds = []): ChangeLog
    {
        $database = $this->createStub(Database::class);
        $database->method('getDefaultConnection')->willReturn($this->createStub(AdapterInterface::class));

        $productHelper = $this->createStub(\Infrangible\Core\Helper\Product::class);
        $productHelper->method('getParentIds')->willReturn($parentIds);

        $productType = $this->createStub(Type::class);
        $productType->method('getCompositeTypes')->willReturn(['configurable', 'bundle', 'grouped']);

        $models = $this->createStub(Models::class);
        $models->method('getChangedAttributeCodes')->willReturn($changedAttributeCodes);

        $variables = new Variables();

        return new ChangeLog(
            $this->createStub(Stores::class),
            $database,
            $productHelper,
            $this->createStub(EntityType::class),
            $this->createStub(Data::class),
            new NullLogger(),
            $productType,
            $this->createStub(CollectionFactory::class),
            $variables,
            $models,
            new Arrays($variables)
        );
    }

    /**
     * Returns a product with the ID as string, like loaded from the database.
     *
     * @param string $typeId
     * @param array $data
     * @param array $origData
     * @phpstan-param array<string, mixed> $data
     * @phpstan-param array<string, mixed> $origData
     *
     * @return Product
     */
    private function createProduct(string $typeId = 'simple', array $data = [], array $origData = []): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn('5');
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getData')->willReturnCallback(fn(string $key): mixed => $data[ $key ] ?? null);
        $product->method('getOrigData')->willReturnCallback(fn(string $key): mixed => $origData[ $key ] ?? null);

        return $product;
    }

    /**
     * Simple products are added with their parents, whose feed contains them; composite products alone.
     *
     * @return void
     * @throws Exception
     */
    public function testCollectProductIdsAddsTheParentsOfSimpleProducts(): void
    {
        $helper = $this->createHelper([], [7, 9]);

        $this->assertSame([5, 7, 9], $helper->collectProductIds($this->createProduct()));
        $this->assertSame([5], $helper->collectProductIds($this->createProduct('configurable')));
    }

    /**
     * A price update is required if a price attribute changed.
     *
     * @return void
     */
    public function testIsPriceUpdateRequired(): void
    {
        $product = $this->createProduct();

        $this->assertTrue($this->createHelper(['name', 'special_price'])->isPriceUpdateRequired($product));
        $this->assertFalse($this->createHelper(['name'])->isPriceUpdateRequired($product));
    }

    /**
     * Changed categories count as changed attribute category_ids.
     *
     * @return void
     */
    public function testGetChangedAttributeCodesAddsChangedCategories(): void
    {
        $product = $this->createProduct('simple', ['affected_category_ids' => [3]]);

        $this->assertSame(['name', 'category_ids'], $this->createHelper(['name'])->getChangedAttributeCodes($product));
    }

    /**
     * A stock update is required if the stock status changed, not if only the quantity changed.
     *
     * @return void
     */
    public function testIsStockUpdateRequired(): void
    {
        $helper = $this->createHelper();

        $this->assertTrue(
            $helper->isStockUpdateRequired(
                $this->createProduct(
                    'simple',
                    ['quantity_and_stock_status' => ['is_in_stock' => '0', 'qty' => 0]],
                    ['quantity_and_stock_status' => ['is_in_stock' => '1', 'qty' => 1]]
                )
            )
        );
        $this->assertFalse(
            $helper->isStockUpdateRequired(
                $this->createProduct(
                    'simple',
                    ['quantity_and_stock_status' => ['is_in_stock' => '1', 'qty' => 5]],
                    ['quantity_and_stock_status' => ['is_in_stock' => '1', 'qty' => 1]]
                )
            )
        );
        $this->assertFalse($helper->isStockUpdateRequired($this->createProduct()));
    }
}
