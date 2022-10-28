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
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use TNW\Stripe\Api\StoredCardsManagementInterface;

class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'TNW_Stripe::payment_info';

    /**
     * @var StoredCardsManagementInterface
     */
    private $cardsManagement;

    /**
     * @param Context $context
     * @param StoredCardsManagementInterface $cardsManagement
     */
    public function __construct(
        Context $context,
        StoredCardsManagementInterface $cardsManagement
    ) {
        parent::__construct($context);
        $this->cardsManagement = $cardsManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $customerId = (int) $this->getRequest()->getParam('id');
        $publicHash = $this->getRequest()->getParam('public_hash');
        $response = ['success' => false, 'message' => '',];
        if (!$customerId || !$publicHash) {
            $response['message'] = __('Invalid Request.');
            return $result->setData($response);
        }
        try {
            if (!$this->cardsManagement->delete($publicHash, $customerId)) {
                $response['message'] = __('Invalid Request.');
                return $result->setData($response);
            }
            $response['success'] = true;
            $response['message'] = __('Stored Card deleted.');
        } catch (\Exception $exception) {
            $response['message'] = $exception->getMessage();
        }
        return $result->setData($response);
    }
}
