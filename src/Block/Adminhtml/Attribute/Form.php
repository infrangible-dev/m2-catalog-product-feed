<?php /** @noinspection PhpDeprecationInspection The constructor of Magento's forms needs the registry. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Block\Adminhtml\Attribute;

use FeWeDev\Base\Arrays;
use Infrangible\CatalogProductFeed\Model\Config\Source\Attribute\Custom;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

/**
 * The form of a feed attribute: integrations add their fields in prepareAttributeFields(), observers of the event
 * infrangible_catalogproductfeed_attribute_edit_form_prepare_form theirs.
 */
abstract class Form extends \Infrangible\BackendWidget\Block\Form
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Arrays $arrays
     * @param \Infrangible\Core\Helper\Registry $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Form $formHelper
     * @param Custom $sourceCustomAttributes
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Arrays $arrays,
        \Infrangible\Core\Helper\Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Form $formHelper,
        protected Custom $sourceCustomAttributes,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $arrays, $registryHelper, $formHelper, $data);
    }

    /**
     * Adds the fields of the attribute.
     *
     * @param \Magento\Framework\Data\Form $form
     *
     * @return void
     */
    protected function prepareFields(\Magento\Framework\Data\Form $form): void
    {
        $fieldSet = $form->addFieldset('attribute', ['legend' => __('Attribute Data')]);

        $this->addTextField($fieldSet, 'attribute_code', __('Feed Attribute Code')->render(), true);
        $this->addEavAttributeField($fieldSet, 'eav_attribute_id', __('Product Attribute as Source')->render());
        $this->addOptionsField(
            $fieldSet,
            'custom_field',
            __('Custom Field')->render(),
            $this->sourceCustomAttributes->toOptionArray(),
            null
        );
        $this->addTextField($fieldSet, 'fixed_value', __('Fixed Value')->render());
        $this->addTextField($fieldSet, 'value_format', __('Value Format')->render());
        $this->addYesNoField($fieldSet, 'is_product', __('Product Attribute')->render());
        $this->addYesNoField($fieldSet, 'is_variant', __('Variant Attribute')->render());
        $this->addYesNoField($fieldSet, 'use_for_delta', __('Delta-Index')->render());
        $this->addYesNoField($fieldSet, 'character_data', __('Character Data')->render());
        $this->addYesNoField($fieldSet, 'strip_tags', __('Strip Tags')->render());

        $this->prepareAttributeFields($form, $fieldSet);

        $this->_eventManager->dispatch(
            'infrangible_catalogproductfeed_attribute_edit_form_prepare_form',
            ['form' => $form, 'attribute' => $this->getObject()]
        );
    }

    /**
     * Adds the fields of the integration, for example the hidden field of the integration name.
     *
     * @param \Magento\Framework\Data\Form $form
     * @param Fieldset $attributeDataFieldSet
     *
     * @return void
     */
    abstract protected function prepareAttributeFields(
        \Magento\Framework\Data\Form $form,
        Fieldset $attributeDataFieldSet
    ): void;
}
