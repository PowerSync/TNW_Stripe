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

namespace TNW\Stripe\Controller\StoredCards;

use Magento\Customer\Controller\AccountInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use TNW\Stripe\Api\StoredCardsManagementInterface;

class Save implements AccountInterface, HttpPostActionInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var StoredCardsManagementInterface
     */
    private $storedCardsManagement;

    /**
     * @param Session $session
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param StoredCardsManagementInterface $storedCardsManagement
     */
    public function __construct(
        Session $session,
        RequestInterface $request,
        ResultFactory $resultFactory,
        StoredCardsManagementInterface $storedCardsManagement
    ) {
        $this->session = $session;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->storedCardsManagement = $storedCardsManagement;
    }
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $customer = $this->session->getCustomerDataObject();
        $additionalData = $this->request->getParam('additionalData');
        $token = $this->request->getParam('token');
        $response = ['success' => false, 'message' => '',];
        if (!$customer || !$token) {
            $response['message'] = __('Invalid Request.');
            return $result->setData($response);
        }
        try {
            if (!$this->storedCardsManagement->save(
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
