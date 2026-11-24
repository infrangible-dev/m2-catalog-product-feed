<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Helper;

use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests the custom fields of the configuration and the formatting of image URLs and prices.
 */
class CustomTest extends TestCase
{
    /**
     * Returns the helper with the configured custom fields and the objects of the object manager by class.
     *
     * @param array $config
     * @param array $objects
     * @phpstan-param array<string, mixed> $config
     * @phpstan-param array<string, object> $objects
     *
     * @return Custom
     */
    private function createHelper(array $config = [], array $objects = []): Custom
    {
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $store->method('getBaseCurrencyCode')->willReturn('EUR');

        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStore')->willReturn($store);

        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(fn(string $className): object => $objects[ $className ]);

        $customConfig = $this->createStub(\Infrangible\CatalogProductFeed\Model\Config\Custom::class);
        $customConfig->method('get')->willReturn($config);

        return new Custom($storeHelper, $objectManager, $customConfig);
    }

    /**
     * Image URLs are below catalog/product of the media URL, images without file have no URL.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetImageUrl(): void
    {
        $helper = $this->createHelper();

        $this->assertSame(
            'https://shop.test/media/catalog/product/m/b/mb01.jpg',
            $helper->getImageUrl(1, '/m/b/mb01.jpg')
        );
        $this->assertNull($helper->getImageUrl(1, Custom::NO_SELECTION));
        $this->assertNull($helper->getImageUrl(1, ''));
        $this->assertNull($helper->getImageUrl(1, null));
    }

    /**
     * Prices have two decimals with a dot, and optionally the base currency.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testFormatPrice(): void
    {
        $helper = $this->createHelper();

        $this->assertSame('1234.50', $helper->formatPrice(1, '1234.5'));
        $this->assertSame('34.00 EUR', $helper->formatPrice(1, 34, true));
    }

    /**
     * Custom fields without label or model are ignored, models must be custom fields.
     *
     * @return void
     */
    public function testCustomFields(): void
    {
        $customField = $this->createStub(AbstractCustom::class);

        $helper = $this->createHelper(
            [
                'valid'      => ['label' => 'Valid', 'model' => 'ValidModel'],
                'wrong_type' => ['label' => 'Wrong Type', 'model' => 'WrongModel'],
                'no_model'   => ['label' => 'No Model']
            ],
            ['ValidModel' => $customField, 'WrongModel' => new stdClass()]
        );

        $this->assertSame(
            [
                'valid'      => ['label' => 'Valid', 'model' => 'ValidModel'],
                'wrong_type' => ['label' => 'Wrong Type', 'model' => 'WrongModel']
            ],
            $helper->getCustomFieldData()
        );
        $this->assertSame($customField, $helper->getCustomFieldModel('valid'));
        $this->assertNull($helper->getCustomFieldModel('wrong_type'));
        $this->assertNull($helper->getCustomFieldModel('no_model'));
    }
}
