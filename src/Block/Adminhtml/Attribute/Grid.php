<?php /** @noinspection PhpDeprecationInspection The constructor of Magento's grids needs the backend helper. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductFeed\Block\Adminhtml\Attribute;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Helper\Session;
use Infrangible\CatalogProductFeed\Model\Config\Source\Attribute\Custom;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Framework\Validator\UniversalFactory;

/**
 * The grid of the feed attributes: integrations add their columns in prepareAttributeFields().
 */
abstract class Grid extends \Infrangible\BackendWidget\Block\Grid
{
    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param Database $databaseHelper
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Registry $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Grid $gridHelper
     * @param Session $sessionHelper
     * @param UniversalFactory $universalFactory
     * @param Config $eavConfig
     * @param Custom $sourceCustomAttributes
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Database $databaseHelper,
        Arrays $arrays,
        Variables $variables,
        Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Grid $gridHelper,
        Session $sessionHelper,
        UniversalFactory $universalFactory,
        Config $eavConfig,
        protected Custom $sourceCustomAttributes,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $backendHelper,
            $databaseHelper,
            $arrays,
            $variables,
            $registryHelper,
            $gridHelper,
            $sessionHelper,
            $universalFactory,
            $eavConfig,
            $data
        );
    }

    /**
     * Adds the columns of the attributes.
     *
     * @return void
     * @throws Exception
     */
    protected function prepareFields(): void
    {
        $this->addTextColumn('attribute_code', __('Feed Attribute Code')->render());
        $this->addEavAttributeColumn('eav_attribute_id', __('Product Attribute')->render());
        $this->addOptionsColumn(
            'custom_field',
            __('Custom Field')->render(),
            $this->sourceCustomAttributes->toOptions()
        );
        $this->addTextColumn('fixed_value', __('Fixed Value')->render());
        $this->addYesNoColumn('is_product', __('Is Product?')->render());
        $this->addYesNoColumn('is_variant', __('Is Variant?')->render());
        $this->addYesNoColumn('use_for_delta', __('Use for Delta-Index?')->render());

        $this->prepareAttributeFields();
    }

    /**
     * Adds the columns of the integration.
     *
     * @return void
     */
    abstract protected function prepareAttributeFields(): void;
}
