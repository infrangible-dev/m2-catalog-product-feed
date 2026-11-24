<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Config\Custom;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Magento\Framework\Config\ConverterInterface;

/**
 * Converts the merged etc/catalog_product_feed_attribute.xml into name => label and model of the custom fields.
 */
class Converter implements ConverterInterface
{
    /**
     * Returns the custom fields with name, label and model.
     *
     * @param DOMDocument $source
     *
     * @return array
     * @phpstan-return array<string, array{label: string, model: string}>
     */
    public function convert($source): array
    {
        $result = [];

        $xpath = new DOMXPath($source);

        $customFields = $xpath->query('/config/custom_fields/custom_field');

        if ($customFields === false) {
            return $result;
        }

        foreach ($customFields as $customField) {
            if (! $customField instanceof DOMElement) {
                continue;
            }

            $name = trim($customField->getAttribute('name'));
            $label = $this->getChildValue($customField, 'label');
            $model = $this->getChildValue($customField, 'model');

            if ($name !== '' && $label !== '' && $model !== '') {
                $result[ $name ] = ['label' => $label, 'model' => $model];
            }
        }

        return $result;
    }

    /**
     * Returns the text of the last child element with the name, or an empty string.
     *
     * @param DOMElement $element
     * @param string $name
     *
     * @return string
     */
    private function getChildValue(DOMElement $element, string $name): string
    {
        $value = '';

        foreach ($element->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->nodeName === $name) {
                $value = trim($childNode->textContent);
            }
        }

        return $value;
    }
}
