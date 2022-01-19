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
namespace TNW\Stripe\Controller\PaymentIntent;

use Magento\Framework\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use TNW\Stripe\Model\CreatePaymentIntent;

/**
 * Class Create
 * Perform Payment and Customer Api requests to Stripe.
 */
class Create extends Action\Action
{
    /**
     * @var Session
     */
    private $session;

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
     * @param Action\Context $context
     * @param Session $session
     * @param CheckoutSession $checkoutSession
     * @param CreatePaymentIntent $createPaymentIntent
     */
    public function __construct(
        Action\Context $context,
        Session $session,
        CheckoutSession $checkoutSession,
        CreatePaymentIntent $createPaymentIntent
    ) {
        parent::__construct($context);
        $this->createPaymentIntent = $createPaymentIntent;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $data = json_decode($this->_request->getParam('data'));
        try {
            $paymentIntent = $this->createPaymentIntent
                ->getPaymentIntent($data, $this->checkoutSession->getQuote(), $this->session->isLoggedIn());
            // 3ds could be done automaticly, need check that and skeep on frontend
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
