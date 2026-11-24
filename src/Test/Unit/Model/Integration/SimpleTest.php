<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Model\Integration;

use ArrayIterator;
use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Model\Attribute;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use PHPUnit\Framework\TestCase;

/**
 * Tests the values of the feed attributes: from product attributes, custom fields and fixed values, without HTML
 * tags and formatted, for products and variants.
 */
class SimpleTest extends TestCase
{
    /**
     * Returns the integration; the product attributes with ID 73, 81 and 99 are name, description and price.
     *
     * @return RecordingIntegration
     */
    private function createIntegration(): RecordingIntegration
    {
        $eavAttributes = [
            '73'  => $this->createEavAttribute('name'),
            '81'  => $this->createEavAttribute('description'),
            '99'  => $this->createEavAttribute('price'),
            'sku' => $this->createEavAttribute('sku')
        ];

        $attributeHelper = $this->createStub(\Infrangible\Core\Helper\Attribute::class);
        $attributeHelper->method('getAttribute')->willReturnCallback(
            fn(string $entityTypeCode, string $attributeCode): EavAttribute => $eavAttributes[ $attributeCode ]
        );

        $customField = $this->createStub(AbstractCustom::class);
        $customField->method('process')->willReturnCallback(
            fn(int $storeId, array $productData, bool $isChild): string => $isChild ? 'variant' : 'product'
        );
        $customField->method('requireEavAttributeCodes')->willReturn(['sku']);

        $customHelper = $this->createStub(Custom::class);
        $customHelper->method('getCustomFieldModel')->willReturn($customField);

        $variables = new Variables();

        return new RecordingIntegration($variables, new Arrays($variables), $attributeHelper, $customHelper);
    }

    /**
     * Returns a product attribute.
     *
     * @param string $code
     *
     * @return EavAttribute
     */
    private function createEavAttribute(string $code): EavAttribute
    {
        $eavAttribute = $this->createStub(EavAttribute::class);
        $eavAttribute->method('getAttributeCode')->willReturn($code);

        return $eavAttribute;
    }

    /**
     * Returns a feed attribute.
     *
     * @param int $id
     * @param string $code
     * @param array $settings
     * @phpstan-param array<string, mixed> $settings
     *
     * @return Attribute
     */
    private function createAttribute(int $id, string $code, array $settings): Attribute
    {
        $attribute = $this->createStub(Attribute::class);
        $attribute->method('getId')->willReturn((string)$id);
        $attribute->method('getAttributeCode')->willReturn($code);
        $attribute->method('getEavAttributeId')->willReturn($settings[ 'eav_attribute_id' ] ?? null);
        $attribute->method('getCustomField')->willReturn($settings[ 'custom_field' ] ?? null);
        $attribute->method('getFixedValue')->willReturn($settings[ 'fixed_value' ] ?? null);
        $attribute->method('getValueFormat')->willReturn($settings[ 'value_format' ] ?? null);
        $attribute->method('isProduct')->willReturn($settings[ 'is_product' ] ?? true);
        $attribute->method('isVariant')->willReturn($settings[ 'is_variant' ] ?? false);
        $attribute->method('isStripTags')->willReturn($settings[ 'strip_tags' ] ?? false);

        return $attribute;
    }

    /**
     * Returns a collection of the feed attributes.
     *
     * @param Attribute[] $attributes
     *
     * @return Collection
     */
    private function createCollection(array $attributes): Collection
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new ArrayIterator($attributes));

        return $collection;
    }

    /**
     * Products and variants get the values of their feed attributes.
     *
     * @return void
     * @throws Exception
     */
    public function testExportsTheValuesOfTheFeedAttributes(): void
    {
        $integration = $this->createIntegration();

        $integration->startStore(
            'full',
            1,
            [],
            $this->createCollection(
                [
                    $this->createAttribute(1, 'title', ['eav_attribute_id' => 73, 'is_variant' => true]),
                    $this->createAttribute(
                        2,
                        'text',
                        ['eav_attribute_id' => 81, 'strip_tags' => true, 'value_format' => '[%s]']
                    ),
                    $this->createAttribute(3, 'custom', ['custom_field' => 'test', 'is_variant' => true]),
                    $this->createAttribute(
                        4,
                        'shop',
                        ['fixed_value' => 'Luma', 'is_product' => false, 'is_variant' => true]
                    ),
                    $this->createAttribute(5, 'price', ['eav_attribute_id' => 99, 'value_format' => '%d %d'])
                ]
            )
        );

        $this->assertSame(['name', 'description', 'sku', 'price'], $integration->getRequiredEavAttributeCodes());

        $productData = ['name' => 'Bag', 'description' => ' <p>Big &amp; <b>blue</b>&nbsp;</p> ', 'price' => '34'];

        $integration->exportProductData('full', 1, $productData, [], [], [], [], [], [], [], [], []);
        $integration->exportChildData('full', 1, ['name' => 'Bag S'], [], [], [], [], []);

        $this->assertSame(
            [
                'product' => [
                    ['title' => 'Bag', 'text' => '[Big & blue]', 'custom' => 'product', 'price' => '34']
                ],
                'child'   => [
                    ['title' => 'Bag S', 'custom' => 'variant', 'shop' => 'Luma']
                ]
            ],
            $integration->exported
        );
    }

    /**
     * The attributes of a store replace the attributes of the store before.
     *
     * @return void
     * @throws Exception
     */
    public function testAttributesOfTheNextStoreReplaceTheFormerOnes(): void
    {
        $integration = $this->createIntegration();

        $integration->startStore(
            'full',
            1,
            [],
            $this->createCollection([$this->createAttribute(1, 'title', ['eav_attribute_id' => 73])])
        );
        $integration->startStore(
            'full',
            2,
            [],
            $this->createCollection([$this->createAttribute(4, 'shop', ['fixed_value' => 'Luma'])])
        );

        $integration->exportProductData('full', 2, ['name' => 'Bag'], [], [], [], [], [], [], [], [], []);

        $this->assertSame(['product' => [['shop' => 'Luma']]], $integration->exported);
        $this->assertSame([], $integration->getRequiredEavAttributeCodes());
    }
}
