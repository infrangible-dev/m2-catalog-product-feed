<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Test\Unit\Model\Config\Custom;

use DOMDocument;
use Infrangible\CatalogProductFeed\Model\Config\Custom\Converter;
use PHPUnit\Framework\TestCase;

/**
 * Tests the conversion of the custom fields of etc/catalog_product_feed_attribute.xml.
 */
class ConverterTest extends TestCase
{
    /**
     * Custom fields need a name, a label and a model.
     *
     * @return void
     */
    public function testConvert(): void
    {
        $document = new DOMDocument();
        $document->loadXML(
            '<config><custom_fields>' .
            '<custom_field name="price"><label>Price</label><model>Vendor\Price</model></custom_field>' .
            '<custom_field name="no_model"><label>No Model</label></custom_field>' .
            '<custom_field><label>No Name</label><model>Vendor\NoName</model></custom_field>' .
            '</custom_fields></config>'
        );

        $this->assertSame(
            ['price' => ['label' => 'Price', 'model' => 'Vendor\Price']],
            (new Converter())->convert($document)
        );
    }
}
