<?php
/**
 * TNW_Stripe extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  TNW
 * @package   TNW_Stripe
 * @copyright Copyright (c) 2017-2022
 * @license   Open Software License (OSL 3.0)
 */

namespace TNW\Stripe\Block\Adminhtml;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;
use TNW\Stripe\Gateway\Config\Config;
use Magento\Framework\View\Element\Context;

class StoredCardsInfoTab extends TabWrapper
{
    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * @return Phrase
     */
    public function getTabLabel()
    {
        return __('Stored Stripe Cards');
    }

    /**
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('customer/storedcards/index', ['_current' => true]);
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return $this->config->isActive();
    }
}
