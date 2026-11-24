<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Integration;

use ArgumentCountError;
use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Model\Attribute;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use ValueError;

/**
 * Base of integrations: collects the feed attributes of the integration and calculates their values for a product.
 */
abstract class Base implements IIntegration
{
    /** @var string[] the codes of the feed attributes by attribute ID */
    protected array $attributeCodes = [];

    /** @var int[] the IDs of the product attributes by attribute ID */
    private array $eavAttributeIds = [];

    /** @var string[] the codes of the product attributes by attribute ID */
    private array $eavAttributeCodes = [];

    /** @var AbstractCustom[] the custom fields by attribute ID */
    private array $customFields = [];

    /** @var string[] the fixed values by attribute ID */
    private array $fixedValues = [];

    /** @var bool[] the attributes of products by attribute ID */
    protected array $productAttributes = [];

    /** @var bool[] the attributes of variants by attribute ID */
    protected array $variantAttributes = [];

    /** @var string[] the sprintf() formats by attribute ID */
    protected array $valueFormats = [];

    /** @var string[] the codes of the attributes written as character data by attribute ID */
    protected array $characterDataAttributes = [];

    /** @var string[] the codes of the product attributes the integration needs */
    protected array $requiredEavAttributeCodes = [];

    /** @var string[] the codes of the attributes without HTML tags by attribute ID */
    protected array $stripTagsAttributeCodes = [];

    /**
     * @param Variables $variables
     * @param Arrays $arrays
     * @param \Infrangible\Core\Helper\Attribute $attributeHelper
     * @param Custom $productFeedCustomHelper
     */
    public function __construct(
        protected Variables $variables,
        protected Arrays $arrays,
        protected \Infrangible\Core\Helper\Attribute $attributeHelper,
        protected Custom $productFeedCustomHelper
    ) {
    }

    /**
     * Collects the feed attributes; attributes of an earlier store are replaced.
     *
     * @param Collection $attributeCollection
     *
     * @return void
     * @throws Exception
     */
    protected function setupAttributes(Collection $attributeCollection): void
    {
        $this->attributeCodes = [];
        $this->eavAttributeIds = [];
        $this->eavAttributeCodes = [];
        $this->customFields = [];
        $this->fixedValues = [];
        $this->productAttributes = [];
        $this->variantAttributes = [];
        $this->valueFormats = [];
        $this->characterDataAttributes = [];
        $this->requiredEavAttributeCodes = [];
        $this->stripTagsAttributeCodes = [];

        foreach ($attributeCollection as $attribute) {
            if ($attribute instanceof Attribute) {
                $this->setupAttribute($attribute);
            }
        }
    }

    /**
     * Collects the feed attribute.
     *
     * @param Attribute $attribute
     *
     * @return void
     * @throws Exception
     */
    protected function setupAttribute(Attribute $attribute): void
    {
        $attributeId = $this->variables->intValue($attribute->getId());

        $this->attributeCodes[ $attributeId ] = $attribute->getAttributeCode();

        $eavAttributeId = $attribute->getEavAttributeId();

        if ($eavAttributeId !== null) {
            $this->addEavAttribute($attributeId, $eavAttributeId);
        }

        $customField = $attribute->getCustomField();

        if ($customField !== null) {
            $this->addCustomFieldAttribute($attributeId, $customField);
        }

        $fixedValue = $attribute->getFixedValue();

        if ($fixedValue !== null) {
            $this->addFixedValueAttribute($attributeId, $fixedValue);
        }

        if ($attribute->isProduct()) {
            $this->addProductAttribute($attributeId);
        }

        if ($attribute->isVariant()) {
            $this->addVariantAttribute($attributeId);
        }

        if ($attribute->isCharacterData()) {
            $this->characterDataAttributes[ $attributeId ] = $attribute->getAttributeCode();
        }

        if ($attribute->isStripTags()) {
            $this->stripTagsAttributeCodes[ $attributeId ] = $attribute->getAttributeCode();
        }

        $valueFormat = $attribute->getValueFormat();

        if ($valueFormat !== null) {
            $this->valueFormats[ $attributeId ] = $valueFormat;
        }
    }

    /**
     * Returns the codes of the feed attributes of products by attribute ID.
     *
     * @return string[]
     */
    protected function getProductAttributeCodes(): array
    {
        return array_intersect_key($this->attributeCodes, $this->productAttributes);
    }

    /**
     * Returns the codes of the feed attributes of variants by attribute ID.
     *
     * @return string[]
     */
    protected function getVariantAttributeCodes(): array
    {
        return array_intersect_key($this->attributeCodes, $this->variantAttributes);
    }

    /**
     * Returns the codes of the product attributes the integration needs.
     *
     * @return string[]
     */
    public function getRequiredEavAttributeCodes(): array
    {
        return array_values(array_unique($this->requiredEavAttributeCodes));
    }

    /**
     * Returns the code of the product attribute of the feed attribute.
     *
     * @param int $attributeId
     *
     * @return string
     */
    protected function getEavAttributeCode(int $attributeId): string
    {
        return $this->eavAttributeCodes[ $attributeId ] ?? '';
    }

    /**
     * Uses the product attribute as source of the feed attribute.
     *
     * @param int $attributeId
     * @param int $eavAttributeId
     *
     * @return void
     * @throws Exception
     */
    protected function addEavAttribute(int $attributeId, int $eavAttributeId): void
    {
        $eavAttribute = $this->attributeHelper->getAttribute(Product::ENTITY, (string)$eavAttributeId);
        $eavAttributeCode = (string)$eavAttribute->getAttributeCode();

        $this->eavAttributeIds[ $attributeId ] = $eavAttributeId;
        $this->eavAttributeCodes[ $attributeId ] = $eavAttributeCode;
        $this->requiredEavAttributeCodes[] = $eavAttributeCode;
    }

    /**
     * Returns whether the feed attribute uses a product attribute as source.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isEavAttribute(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->eavAttributeIds);
    }

    /**
     * Returns the custom field of the feed attribute.
     *
     * @param int $attributeId
     *
     * @return AbstractCustom|null
     */
    protected function getCustomField(int $attributeId): ?AbstractCustom
    {
        return $this->customFields[ $attributeId ] ?? null;
    }

    /**
     * Uses the custom field as source of the feed attribute.
     *
     * @param int $attributeId
     * @param string $customField
     *
     * @return void
     * @throws Exception
     */
    protected function addCustomFieldAttribute(int $attributeId, string $customField): void
    {
        $customFieldModel = $this->productFeedCustomHelper->getCustomFieldModel($customField);

        if ($customFieldModel === null) {
            throw new LocalizedException(__('Could not find model for custom field: %1', $customField));
        }

        $this->customFields[ $attributeId ] = $customFieldModel;

        foreach ($customFieldModel->requireEavAttributeCodes() as $requireEavAttributeCode) {
            if ($requireEavAttributeCode !== '') {
                $eavAttribute = $this->attributeHelper->getAttribute(Product::ENTITY, $requireEavAttributeCode);

                $this->requiredEavAttributeCodes[] = (string)$eavAttribute->getAttributeCode();
            }
        }
    }

    /**
     * Returns whether the feed attribute uses a custom field as source.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isCustomField(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->customFields);
    }

    /**
     * Returns the fixed value of the feed attribute.
     *
     * @param int $attributeId
     *
     * @return string|null
     */
    protected function getFixedValue(int $attributeId): ?string
    {
        return $this->fixedValues[ $attributeId ] ?? null;
    }

    /**
     * Uses the fixed value as value of the feed attribute.
     *
     * @param int $attributeId
     * @param string $fixedValue
     *
     * @return void
     */
    protected function addFixedValueAttribute(int $attributeId, string $fixedValue): void
    {
        $this->fixedValues[ $attributeId ] = $fixedValue;
    }

    /**
     * Returns whether the feed attribute has a fixed value.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isFixedValue(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->fixedValues);
    }

    /**
     * Exports the feed attribute for products.
     *
     * @param int $attributeId
     *
     * @return void
     */
    protected function addProductAttribute(int $attributeId): void
    {
        $this->productAttributes[ $attributeId ] = true;
    }

    /**
     * Returns the feed attributes of products as attribute ID => true.
     *
     * @return bool[]
     */
    protected function getProductAttributes(): array
    {
        return $this->productAttributes;
    }

    /**
     * Returns whether the feed attribute is exported for products.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isProductAttribute(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->productAttributes);
    }

    /**
     * Exports the feed attribute for variants.
     *
     * @param int $attributeId
     *
     * @return void
     */
    protected function addVariantAttribute(int $attributeId): void
    {
        $this->variantAttributes[ $attributeId ] = true;
    }

    /**
     * Returns whether the feed attribute is exported for variants.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isVariantAttribute(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->variantAttributes);
    }

    /**
     * Returns whether the integration writes the value of the feed attribute as character data.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isCharacterData(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->characterDataAttributes);
    }

    /**
     * Returns whether HTML tags are removed from the value of the feed attribute.
     *
     * @param int $attributeId
     *
     * @return bool
     */
    protected function isStripTags(int $attributeId): bool
    {
        return array_key_exists($attributeId, $this->stripTagsAttributeCodes);
    }

    /**
     * Returns the sprintf() format of the feed attribute, or null.
     *
     * @param int $attributeId
     *
     * @return string|null
     */
    protected function getValueFormat(int $attributeId): ?string
    {
        return $this->valueFormats[ $attributeId ] ?? null;
    }

    /**
     * Returns the values of the feed attributes for the product as attribute code => value.
     *
     * @param string $mode
     * @param string[] $attributes the codes of the feed attributes by attribute ID
     * @param int $storeId
     * @param array $productData
     * @param bool $isVariant
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @param array $reviewSummary
     * @param array $children
     * @param array $bundled
     * @param array $grouped
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     * @phpstan-param array<string, mixed> $reviewSummary
     * @phpstan-param array<int|string, array<string, mixed>> $children
     * @phpstan-param array<int|string, array<string, mixed>> $bundled
     * @phpstan-param array<int|string, array<string, mixed>> $grouped
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    protected function getExportData(
        string $mode,
        array $attributes,
        int $storeId,
        array $productData,
        bool $isVariant,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $reviewSummary,
        array $children,
        array $bundled,
        array $grouped
    ): array {
        $exportData = [];

        foreach ($attributes as $attributeId => $attributeCode) {
            if ($this->canExportValue($mode, $storeId, $isVariant, $attributeId)) {
                $exportData[ $attributeCode ] = $this->processExportValue(
                    $attributeId,
                    $storeId,
                    $productData,
                    $isVariant,
                    $categoryPaths,
                    $urlRewrites,
                    $galleryImages,
                    $indexedPrices,
                    $stockItem,
                    $reviewSummary,
                    $children,
                    $bundled,
                    $grouped
                );
            }
        }

        return $exportData;
    }

    /**
     * Returns whether the value of the feed attribute is exported; integrations can skip values.
     *
     * @param string $mode
     * @param int $storeId
     * @param bool $isVariant
     * @param int $attributeId
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    protected function canExportValue(string $mode, int $storeId, bool $isVariant, int $attributeId): bool
    {
        return true;
    }

    /**
     * Returns the value of the feed attribute for the product.
     *
     * The value comes from its custom field, fixed value or product attribute, without HTML tags and formatted if
     * configured.
     *
     * @param int $attributeId
     * @param int $storeId
     * @param array $productData
     * @param bool $isVariant
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @param array $reviewSummary
     * @param array $children
     * @param array $bundled
     * @param array $grouped
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     * @phpstan-param array<string, mixed> $reviewSummary
     * @phpstan-param array<int|string, array<string, mixed>> $children
     * @phpstan-param array<int|string, array<string, mixed>> $bundled
     * @phpstan-param array<int|string, array<string, mixed>> $grouped
     *
     * @return mixed
     * @noinspection PhpUnusedParameterInspection
     */
    protected function processExportValue(
        int $attributeId,
        int $storeId,
        array $productData,
        bool $isVariant,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $reviewSummary,
        array $children,
        array $bundled,
        array $grouped
    ): mixed {
        $exportValue = null;

        $customField = $this->getCustomField($attributeId);

        if ($customField !== null) {
            $exportValue = $this->processCustomField(
                $customField,
                $storeId,
                $productData,
                $isVariant,
                $categoryPaths,
                $urlRewrites,
                $galleryImages,
                $indexedPrices,
                $stockItem,
                $children,
                $bundled,
                $grouped
            );
        } elseif ($this->isFixedValue($attributeId)) {
            $exportValue = $this->getFixedValue($attributeId);
        } elseif ($this->isEavAttribute($attributeId)) {
            $exportValue = $productData[ $this->getEavAttributeCode($attributeId) ] ?? null;
        }

        return $this->formatValue($attributeId, $this->stripTags($attributeId, $exportValue));
    }

    /**
     * Returns the value of the custom field for the product.
     *
     * @param AbstractCustom $customField
     * @param int $storeId
     * @param array $productData
     * @param bool $isVariant
     * @param array $categoryPaths
     * @param array $urlRewrites
     * @param array $galleryImages
     * @param array $indexedPrices
     * @param array $stockItem
     * @param array $children
     * @param array $bundled
     * @param array $grouped
     * @phpstan-param array<string, mixed> $productData
     * @phpstan-param array<int|string, array<string, mixed>> $categoryPaths
     * @phpstan-param array<int|string, mixed> $urlRewrites
     * @phpstan-param array<int|string, mixed> $galleryImages
     * @phpstan-param array<string, mixed> $indexedPrices
     * @phpstan-param array<string, mixed> $stockItem
     * @phpstan-param array<int|string, array<string, mixed>> $children
     * @phpstan-param array<int|string, array<string, mixed>> $bundled
     * @phpstan-param array<int|string, array<string, mixed>> $grouped
     *
     * @return mixed
     */
    protected function processCustomField(
        AbstractCustom $customField,
        int $storeId,
        array $productData,
        bool $isVariant,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $children,
        array $bundled,
        array $grouped
    ): mixed {
        return $customField->process(
            $storeId,
            $productData,
            $isVariant,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            $children,
            $bundled,
            $grouped
        );
    }

    /**
     * Removes HTML tags and entities from a text value, if configured for the feed attribute.
     *
     * @param int $attributeId
     * @param mixed $value
     *
     * @return mixed
     */
    protected function stripTags(int $attributeId, mixed $value): mixed
    {
        if (! $this->isStripTags($attributeId) || ! is_scalar($value)) {
            return $value;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- feeds need the text, not the HTML entities
        $text = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // also removes the non-breaking spaces of &nbsp;, which trim() keeps
        return preg_replace('/^[\s\x00]+|[\s\x00]+$/u', '', $text) ?? trim($text);
    }

    /**
     * Formats a scalar value, or the value of an option, with the sprintf() format of the feed attribute.
     *
     * Options are arrays with id and value. Values that do not fit the format are kept.
     *
     * @param int $attributeId
     * @param mixed $value
     *
     * @return mixed
     */
    protected function formatValue(int $attributeId, mixed $value): mixed
    {
        $valueFormat = $this->getValueFormat($attributeId);

        if ($valueFormat === null) {
            return $value;
        }

        if (is_scalar($value)) {
            return $this->format($valueFormat, $value);
        }

        if (is_array($value) && array_key_exists('id', $value) && array_key_exists('value', $value) &&
            is_scalar($value[ 'value' ])) {
            $value[ 'value' ] = $this->format($valueFormat, $value[ 'value' ]);
        }

        return $value;
    }

    /**
     * Returns the value formatted with the sprintf() format, or the value if it does not fit the format.
     *
     * @param string $format
     * @param bool|float|int|string $value
     *
     * @return bool|float|int|string
     */
    private function format(string $format, bool|float|int|string $value): bool|float|int|string
    {
        try {
            return sprintf($format, $value);
        } catch (ValueError | ArgumentCountError) {
            return $value;
        }
    }
}
