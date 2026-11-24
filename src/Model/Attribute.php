<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * An attribute of a feed: the value comes from a product attribute, a custom field or a fixed value, and is exported
 * under the attribute code of the feed.
 */
class Attribute extends AbstractModel
{
    /** @var string */
    protected $_eventPrefix = 'infrangible_catalogproductfeed_attribute';

    /** @var string */
    protected $_eventObject = 'attribute';

    /**
     * Sets the resource model.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Attribute::class);
    }

    /**
     * Returns the name of the integration the attribute belongs to.
     *
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->stringValue('integration') ?? '';
    }

    /**
     * Returns the code under which the value is exported.
     *
     * @return string
     */
    public function getAttributeCode(): string
    {
        return $this->stringValue('attribute_code') ?? '';
    }

    /**
     * Returns the ID of the product attribute whose value is exported, or null.
     *
     * @return int|null
     */
    public function getEavAttributeId(): ?int
    {
        $eavAttributeId = $this->getData('eav_attribute_id');

        return is_numeric($eavAttributeId) && (int)$eavAttributeId > 0 ? (int)$eavAttributeId : null;
    }

    /**
     * Returns the name of the custom field whose value is exported, or null.
     *
     * @return string|null
     */
    public function getCustomField(): ?string
    {
        return $this->stringValue('custom_field');
    }

    /**
     * Returns the fixed value that is exported, or null.
     *
     * @return string|null
     */
    public function getFixedValue(): ?string
    {
        return $this->stringValue('fixed_value');
    }

    /**
     * Returns the sprintf() format of the value, or null.
     *
     * @return string|null
     */
    public function getValueFormat(): ?string
    {
        return $this->stringValue('value_format');
    }

    /**
     * Returns the position of the attribute in the feed.
     *
     * @return int
     */
    public function getPosition(): int
    {
        $position = $this->getData('position');

        return is_numeric($position) ? (int)$position : 0;
    }

    /**
     * Returns whether the attribute is exported for products.
     *
     * @return bool
     */
    public function isProduct(): bool
    {
        return (bool)$this->getData('is_product');
    }

    /**
     * Returns whether the attribute is exported for the variants of products (children, bundled and grouped).
     *
     * @return bool
     */
    public function isVariant(): bool
    {
        return (bool)$this->getData('is_variant');
    }

    /**
     * Returns whether the attribute is exported in the delta export.
     *
     * @return bool
     */
    public function useForDelta(): bool
    {
        return (bool)$this->getData('use_for_delta');
    }

    /**
     * Returns whether the integration writes the value as character data.
     *
     * @return bool
     */
    public function isCharacterData(): bool
    {
        return (bool)$this->getData('character_data');
    }

    /**
     * Returns whether HTML tags are removed from the value.
     *
     * @return bool
     */
    public function isStripTags(): bool
    {
        return (bool)$this->getData('strip_tags');
    }

    /**
     * Sets the creation and update time.
     *
     * @return $this
     */
    public function beforeSave(): static
    {
        if ($this->isObjectNew()) {
            $this->setData('created_at', gmdate('Y-m-d H:i:s'));
        }

        $this->setData('updated_at', gmdate('Y-m-d H:i:s'));

        parent::beforeSave();

        return $this;
    }

    /**
     * Returns the text value of the field, or null if it is empty.
     *
     * @param string $key
     *
     * @return string|null
     */
    protected function stringValue(string $key): ?string
    {
        $value = $this->getData($key);

        return is_scalar($value) && (string)$value !== '' ? (string)$value : null;
    }
}
