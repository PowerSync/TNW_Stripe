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

namespace TNW\Stripe\Controller\Adminhtml\StoredCards;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\LayoutFactory;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'TNW_Stripe::payment_info';

    /**
     * @var LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @param Context $context
     * @param LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        Context $context,
        LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     */
    public function execute()
    {
        return $this->resultLayoutFactory->create();
    }
}
