<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Helper;

use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\Core\Helper\Stores;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * The custom fields, and the formatting of their values.
 */
class Custom
{
    /** Value of image attributes without image. */
    public const NO_SELECTION = 'no_selection';

    /**
     * @param Stores $storeHelper
     * @param ObjectManagerInterface $objectManager
     * @param \Infrangible\CatalogProductFeed\Model\Config\Custom $config
     */
    public function __construct(
        protected Stores $storeHelper,
        protected ObjectManagerInterface $objectManager,
        protected \Infrangible\CatalogProductFeed\Model\Config\Custom $config
    ) {
    }

    /**
     * Returns the custom fields as name => label and model.
     *
     * @return array
     * @phpstan-return array<string, array{label: string, model: string}>
     */
    public function getCustomFieldData(): array
    {
        $customFields = [];

        $data = $this->config->get();

        if (! is_array($data)) {
            return $customFields;
        }

        foreach ($data as $name => $customField) {
            if (is_array($customField) && isset($customField[ 'label' ], $customField[ 'model' ]) &&
                is_string($customField[ 'label' ]) && is_string($customField[ 'model' ])) {
                $customFields[ (string)$name ] = [
                    'label' => $customField[ 'label' ],
                    'model' => $customField[ 'model' ]
                ];
            }
        }

        return $customFields;
    }

    /**
     * Returns the model of the custom field, or null if the field or its model does not exist.
     *
     * @param string $customFieldName
     *
     * @return AbstractCustom|null
     */
    public function getCustomFieldModel(string $customFieldName): ?AbstractCustom
    {
        $customFieldData = $this->getCustomFieldData();

        if (! array_key_exists($customFieldName, $customFieldData)) {
            return null;
        }

        $customFieldModel = $this->objectManager->get($customFieldData[ $customFieldName ][ 'model' ]);

        return $customFieldModel instanceof AbstractCustom ? $customFieldModel : null;
    }

    /**
     * Returns the URL of the product image in the store, or null without image.
     *
     * @param int $storeId
     * @param mixed $imagePath the path below catalog/product in the media directory, as stored in the attribute
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getImageUrl(int $storeId, mixed $imagePath): ?string
    {
        if (! is_string($imagePath) || $imagePath === '' || $imagePath === self::NO_SELECTION) {
            return null;
        }

        $store = $this->storeHelper->getStore($storeId);

        return rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/') . '/catalog/product/' .
            ltrim($imagePath, '/');
    }

    /**
     * Formats the price with two decimals and a dot, optionally followed by the base currency of the store.
     *
     * The prices of the index are in the base currency.
     *
     * @param int $storeId
     * @param float|int|string $price
     * @param bool $withCurrency
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function formatPrice(int $storeId, float|int|string $price, bool $withCurrency = false): string
    {
        $formattedPrice = number_format(is_numeric($price) ? (float)$price : 0.0, 2, '.', '');

        if ($withCurrency) {
            $formattedPrice .= ' ' . $this->storeHelper->getStore($storeId)->getBaseCurrencyCode();
        }

        return $formattedPrice;
    }
}
