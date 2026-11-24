<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Config\Source\Attribute;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * The custom fields as options of the attribute form and grid.
 */
class Custom implements OptionSourceInterface
{
    /**
     * @param \Infrangible\CatalogProductFeed\Helper\Custom $productFeedCustomHelper
     */
    public function __construct(protected \Infrangible\CatalogProductFeed\Helper\Custom $productFeedCustomHelper)
    {
    }

    /**
     * Returns the custom fields as name => label.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    public function toOptions(): array
    {
        return array_map(
            static fn(array $data): string => $data[ 'label' ],
            $this->productFeedCustomHelper->getCustomFieldData()
        );
    }

    /**
     * Returns the custom fields as options, with an empty option first.
     *
     * @return array
     * @phpstan-return array<int, array{value: string, label: mixed}>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->toOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
