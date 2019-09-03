<?php

namespace TNW\Stripe\Controller\Paymentintent;

use Magento\Framework\App\Action;
use Magento\Framework\Controller\Result\RawFactory;
use TNW\Stripe\Helper\Payment\Formatter;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use Magento\Framework\Controller\ResultFactory;

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

    /**
     * @var RawFactory
     */
    private $rawFactory;


    /** @var Config  */
    private $config;

    /** @var StripeAdapterFactory  */
    private $adapterFactory;



    public function __construct(
        Action\Context $context,
        RawFactory $rawFactory,
        Config $config,
        StripeAdapterFactory $adapterFactory
    ) {
        parent::__construct($context);
        $this->rawFactory = $rawFactory;
        $this->config = $config;
        $this->adapterFactory = $adapterFactory;
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $data = json_decode($this->_request->getParam('data'));
        $payment  = $data->paymentMethod;
        $amount   = $data->amount;
        $currency = $data->currency;
        $params = [
            self::AMOUNT => $this->formatPrice($amount),
            self::CURRENCY => $currency,
            self::PAYMENT_METHOD_TYPES => ['card'],
            self::CONFIRMATION_METHOD => 'manual'
        ];
        $params[self::PAYMENT_METHOD] = $payment->id;
        $stripeAdapter = $this->adapterFactory->create();
        $paymentIntent = $stripeAdapter->createPaymentIntent($params);
        $response->setData(['pi' => $paymentIntent->client_secret]);
        return $response;
    }
}
