<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Model\Attribute\Custom;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Helper\Data;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\CategoryNames;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\FinalPrice;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\FinalPriceCurrency;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\ParentSku;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\Price;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\ProductCategoryUrls;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\ProductUrl;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\ProductUrlWithKey;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\StockQty;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Product;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the values of the custom fields, with the product data of the export.
 */
class CustomFieldsTest extends TestCase
{
    /**
     * Returns the custom field of the class; URLs are https://shop<store id>.test/<path>, the root category of the
     * stores is 2 and their base currency EUR.
     *
     * @param string $className
     *
     * @return AbstractCustom
     */
    private function createCustomField(string $className): AbstractCustom
    {
        $url = $this->createStub(Url::class);
        $url->method('getUrl')->willReturnCallback(
            fn(string $route, ?bool $isSecure, array $params, ?int $storeId): string => sprintf(
                'https://shop%d.test/%s',
                $storeId,
                $params[ '_direct' ]
            )
        );

        $store = $this->createStub(Store::class);
        $store->method('getRootCategoryId')->willReturn('2');
        $store->method('getBaseCurrencyCode')->willReturn('EUR');

        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStore')->willReturn($store);
        $storeHelper->method('getStoreConfig')->willReturn('.html');

        $customHelper = new Custom(
            $storeHelper,
            $this->createStub(ObjectManagerInterface::class),
            $this->createStub(\Infrangible\CatalogProductFeed\Model\Config\Custom::class)
        );

        $variables = new Variables();

        $arguments = [
            $variables,
            new Arrays($variables),
            $this->createStub(Database::class),
            $url,
            $storeHelper,
            $this->createStub(Data::class),
            $customHelper,
            new NullLogger()
        ];

        if ($className === ProductUrlWithKey::class) {
            $product = $this->createStub(\Magento\Catalog\Model\Product::class);
            $product->method('formatUrlKey')->willReturnCallback(fn(string $urlKey): string => strtolower($urlKey));

            $productHelper = $this->createStub(Product::class);
            $productHelper->method('newProduct')->willReturn($product);

            $arguments[] = $productHelper;
        }

        $customField = new $className(...$arguments);

        $this->assertInstanceOf(AbstractCustom::class, $customField);

        return $customField;
    }

    /**
     * Returns the value of the custom field for the product data.
     *
     * @param string $className
     * @param array $productData
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $indexedPrices
     * @param array $stockItem
     * @param bool $isChild
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     *
     * @return mixed
     */
    private function process(
        string $className,
        array $productData,
        array $categoryPaths = [],
        array $urlRewrites = [],
        array $indexedPrices = [],
        array $stockItem = [],
        bool $isChild = false
    ): mixed {
        return $this->createCustomField($className)->process(
            1,
            $productData,
            $isChild,
            $categoryPaths,
            $urlRewrites,
            [],
            $indexedPrices,
            $stockItem,
            [],
            [],
            []
        );
    }

    /**
     * The URLs of the product are by category, the URL without category under the root category of the store.
     *
     * @return void
     */
    public function testProductCategoryUrls(): void
    {
        $this->assertSame(
            [2 => 'https://shop1.test/bag.html', 4 => 'https://shop1.test/gear/bag.html'],
            $this->process(ProductCategoryUrls::class, [], [], [0 => 'bag.html', 4 => 'gear/bag.html', 5 => ''])
        );
    }

    /**
     * The product URL is the one without category, else the URL path.
     *
     * @return void
     */
    public function testProductUrl(): void
    {
        $this->assertSame(
            'https://shop1.test/bag.html',
            $this->process(ProductUrl::class, ['url_path' => 'old.html'], [], [4 => 'gear/bag.html', 0 => 'bag.html'])
        );
        $this->assertSame('https://shop1.test/old.html', $this->process(ProductUrl::class, ['url_path' => 'old.html']));
    }

    /**
     * The URL with key uses the URL suffix of products of the store.
     *
     * @return void
     */
    public function testProductUrlWithKey(): void
    {
        $this->assertSame(
            'https://shop1.test/bag.html',
            $this->process(ProductUrlWithKey::class, ['url_key' => 'Bag'])
        );
        $this->assertNull($this->process(ProductUrlWithKey::class, []));
    }

    /**
     * Variants have the SKU of their parent.
     *
     * @return void
     */
    public function testParentSku(): void
    {
        $productData = ['sku' => 'MB01-S', 'parent' => ['sku' => 'MB01']];

        $this->assertSame('MB01', $this->process(ParentSku::class, $productData, [], [], [], [], true));
        $this->assertNull($this->process(ParentSku::class, $productData));
    }

    /**
     * Prices come from the price index, configurable, bundle and grouped products have their minimal price.
     *
     * @return void
     */
    public function testPrices(): void
    {
        $prices = ['final_price' => '30.0000', 'min_price' => '25.0000'];

        $this->assertSame(
            '30.00 EUR',
            $this->process(FinalPriceCurrency::class, ['type_id' => 'simple', 'price' => '34'], [], [], $prices)
        );
        $this->assertSame(
            '25.00 EUR',
            $this->process(FinalPriceCurrency::class, ['type_id' => 'bundle'], [], [], $prices)
        );
        $this->assertSame(
            '25.00',
            $this->process(FinalPrice::class, ['type_id' => 'configurable', 'price' => '0'], [], [], $prices)
        );
        $this->assertSame('34.00 EUR', $this->process(FinalPriceCurrency::class, ['price' => '34']));
        $this->assertSame('34.00', $this->process(Price::class, ['price' => '34']));
        $this->assertNull($this->process(Price::class, ['price' => null]));
    }

    /**
     * Category names are by category ID, the stock quantity is an integer.
     *
     * @return void
     */
    public function testCategoryNamesAndStockQty(): void
    {
        $this->assertSame(
            [3 => 'Gear', 4 => 'Bags'],
            $this->process(CategoryNames::class, [], [3 => ['name' => 'Gear'], 4 => ['name' => 'Bags']])
        );
        $this->assertSame(100, $this->process(StockQty::class, [], [], [], [], ['qty' => '100.0000']));
        $this->assertSame(0, $this->process(StockQty::class, []));
    }
}
