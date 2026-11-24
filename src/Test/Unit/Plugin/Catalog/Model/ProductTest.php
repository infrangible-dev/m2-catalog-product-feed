<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Plugin\Catalog\Model;

use Exception;
use Infrangible\CatalogProductFeed\Helper\ChangeLog;
use Infrangible\CatalogProductFeed\Plugin\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

/**
 * Tests which saved products go into the change log.
 */
class ProductTest extends TestCase
{
    /**
     * Returns the change log helper, which expects the number of inserts.
     *
     * @param bool $priceChanged
     * @param bool $stockChanged
     * @param bool $attributeChanged
     * @param int $inserts
     *
     * @return ChangeLog
     */
    private function createChangeLog(
        bool $priceChanged,
        bool $stockChanged,
        bool $attributeChanged,
        int $inserts
    ): ChangeLog {
        $changeLog = $this->createMock(ChangeLog::class);
        $changeLog->method('isPriceUpdateRequired')->willReturn($priceChanged);
        $changeLog->method('isStockUpdateRequired')->willReturn($stockChanged);
        $changeLog->method('isAttributeUpdateRequired')->willReturn($attributeChanged);
        $changeLog->method('collectProductIds')->willReturn([5, 7]);
        $changeLog->expects($this->exactly($inserts))->method('insertEntityIds')->with([5, 7], 'catalog_product');

        return $changeLog;
    }

    /**
     * Price changes are added after the price reindex, other changes after the reindex.
     *
     * @return void
     * @throws Exception
     */
    public function testAddsChangedProducts(): void
    {
        $product = $this->createStub(\Magento\Catalog\Model\Product::class);

        $plugin = new Product($this->createChangeLog(true, true, true, 1));
        $this->assertNull($plugin->afterPriceReindexCallback($product, null));
        $this->assertNull($plugin->afterReindex($product, null));

        $plugin = new Product($this->createChangeLog(false, true, false, 1));
        $plugin->afterPriceReindexCallback($product, null);
        $plugin->afterReindex($product, null);

        $plugin = new Product($this->createChangeLog(false, false, true, 1));
        $plugin->afterReindex($product, null);

        $plugin = new Product($this->createChangeLog(false, false, false, 0));
        $plugin->afterPriceReindexCallback($product, null);
        $plugin->afterReindex($product, null);
    }
}
