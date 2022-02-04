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
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */
namespace TNW\Stripe\Controller\Adminhtml\PaymentIntent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\Session\Quote as CheckoutSession;
use TNW\Stripe\Model\CreatePaymentIntent;

/**
 * Class Create
 */
class Create extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CreatePaymentIntent
     */
    private $createPaymentIntent;

    /**
     * Create constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param CreatePaymentIntent $createPaymentIntent
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CreatePaymentIntent $createPaymentIntent
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->createPaymentIntent = $createPaymentIntent;

    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $paymentIntent = $this->createPaymentIntent->getPaymentIntent(
                json_decode($this->_request->getParam('data')),
                $this->checkoutSession->getQuote(),
                true
            );
            if (is_null($paymentIntent->next_action) && !
                ($paymentIntent->status == "requires_action" || $paymentIntent->status == "requires_source_action")) {
                $response->setData(['skip_3ds' => true, 'paymentIntent' => $paymentIntent]);

                return $response;
            }
            $response->setData(['pi' => $paymentIntent->client_secret]);
        } catch (\Exception $e) {
            $response->setData(['error' => ['message' => $e->getMessage()]]);
        }
        return $response;
    }
}
