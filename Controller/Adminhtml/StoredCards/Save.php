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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use TNW\Stripe\Api\StoredCardsManagementInterface;

class Save extends Action implements HttpPostActionInterface
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
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param Context $context
     * @param StoredCardsManagementInterface $cardsManagement
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        StoredCardsManagementInterface $cardsManagement,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->cardsManagement = $cardsManagement;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $customerId = (int) $this->getRequest()->getParam('id');
        $additionalData = $this->getRequest()->getParam('additionalData');
        $token = $this->getRequest()->getParam('token');

        try {
            $customer = $this->customerRepository->getById($customerId);
            $response = ['success' => false, 'message' => '',];
            if (!$customer || !$token) {
                $response['message'] = __('Invalid Request.');
                return $result->setData($response);
            }
            if (!$this->cardsManagement->save(
                $token,
                $customer,
                [
                    'last4' => $additionalData['cc_last4'] ?? '',
                    'exp_month' => $additionalData['cc_exp_month'] ?? '',
                    'exp_year' => $additionalData['cc_exp_year'] ?? '',
                    'type' => $additionalData['cc_type'] ?? ''
                ]
            )) {
                $response['message'] = __('Invalid Request.');
                return $result->setData($response);
            }
            $response['success'] = true;
            $response['message'] = __('New Card Added');
        } catch (\Exception $exception) {
            $response['message'] = $exception->getMessage();
        }
        return $result->setData($response);
    }
}
