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
 * Edits a feed attribute: integrations extend it with their ACL resource and menu.
 */
abstract class Edit extends \Infrangible\BackendWidget\Controller\Backend\Object\Edit
{
    use Attribute;
}
