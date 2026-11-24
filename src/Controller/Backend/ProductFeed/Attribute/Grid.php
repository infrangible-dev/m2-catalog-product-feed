<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Controller\Backend\ProductFeed\Attribute;

use Infrangible\CatalogProductFeed\Traits\Attribute;

/**
 * The grid of the feed attributes: integrations extend it with their ACL resource and menu.
 */
abstract class Grid extends \Infrangible\BackendWidget\Controller\Backend\Object\Grid
{
    use Attribute;
}
