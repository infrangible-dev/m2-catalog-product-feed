<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Model\Config;

use Infrangible\CatalogProductFeed\Model\Config\Custom\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\Data;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * The custom fields of all modules, from their etc/catalog_product_feed_attribute.xml, as name => label and model.
 */
class Custom extends Data
{
    /**
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param SerializerInterface|null $serializer
     */
    public function __construct(Reader $reader, CacheInterface $cache, ?SerializerInterface $serializer = null)
    {
        parent::__construct($reader, $cache, 'catalog_product_feed_attribute', $serializer);
    }
}
