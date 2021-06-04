<?php

namespace TNW\Stripe\Controller\Paymentintent;

use Magento\Framework\App\Action;
use Magento\Framework\Controller\Result\RawFactory;
use TNW\Stripe\Helper\Payment\Formatter;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Stripe\Helper\Customer as CustomerHelper;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Create
 *
 * @package TNW\Stripe\Controller\Paymentintent
 */
class Create extends Action\Action
{
    use Formatter;

    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const DESCRIPTION = 'description';
    const CONFIRMATION_METHOD = 'confirmation_method';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_METHOD_TYPES = 'payment_method_types';
    const RECEIPT_EMAIL = 'receipt_email';
    const CUSTOMER = 'customer';
    const CAPTURE_METHOD = 'capture_method';
    const SETUP_FUTURE_USAGE = 'setup_future_usage';

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var RawFactory
     */
    private $rawFactory;

    /** @var Config */
    private $config;

    /** @var StripeAdapterFactory */
    private $adapterFactory;

    /** @var Session */
    private $session;

    /** @var CheckoutSession */
    private $checkoutSession;

    /**
     * Create constructor.
     * @param Action\Context $context
     * @param RawFactory $rawFactory
     * @param Config $config
     * @param StripeAdapterFactory $adapterFactory
     * @param Session $session
     * @param CheckoutSession $checkoutSession
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        Action\Context $context,
        RawFactory $rawFactory,
        Config $config,
        StripeAdapterFactory $adapterFactory,
        Session $session,
        CheckoutSession $checkoutSession,
        CustomerHelper $customerHelper
    ) {
        parent::__construct($context);
        $this->rawFactory = $rawFactory;
        $this->config = $config;
        $this->adapterFactory = $adapterFactory;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $data = json_decode($this->_request->getParam('data'));
        $payment = $data->paymentMethod;
        $amount = $data->amount;
        $currency = $data->currency;

        try {
            $attributes = [
                'payment_method' => $payment->id,
                'metadata' => ['site' => $this->_url->getBaseUrl()]
            ];


            // for login customers while enable vault in configuration and vault not checked in frontend case
            $email = $this->checkoutSession->getQuote()->getBillingAddress()->getEmail();
            $isLoggedIn = $this->session->isLoggedIn();
            if (($isLoggedIn)) {
                $attributes['email'] = $email;
            } elseif (!$isLoggedIn) {
                $attributes['email'] = $email;
                $attributes['description'] = 'guest';
            }

            $stripeAdapter = $this->adapterFactory->create();
            $cs = $stripeAdapter->customer($attributes);
            $this->customerHelper->updateCustomerStripeId($attributes['email'], $cs->id);
            $params = [
                self::CUSTOMER => $cs->id,
                self::AMOUNT => $this->formatPrice($amount),
                self::CURRENCY => $currency,
                self::PAYMENT_METHOD_TYPES => ['card'],
                self::CONFIRMATION_METHOD => 'manual',
                self::CAPTURE_METHOD => 'manual',
                self::SETUP_FUTURE_USAGE => 'off_session'
            ];
            $params[self::PAYMENT_METHOD] = $payment->id;
            $paymentIntent = $stripeAdapter->createPaymentIntent($params);
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
