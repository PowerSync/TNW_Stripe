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
namespace TNW\Stripe\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Config;

class CardDetailsHandler implements HandlerInterface
{
    const CARD_NUMBER = 'cc_number';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    private $typeMapper;

    public function __construct(
        SubjectReader $subjectReader,
        Config $typeMapper
    ) {
        $this->typeMapper = $typeMapper;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $subject, array $response)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $this->subjectReader->readTransaction($response);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $ccLats4 = $payment->getAdditionalInformation('cc_last4') ? : $payment->getData('maskedCC');
        $expirationDate = $payment->getData('expirationDate');
        $expMonth = '';
        $ccExpYear = '';
        if ($expirationDate) {
            $expirationDate = explode('/', $expirationDate);
            $expMonth = isset($expirationDate[0]) ? $expirationDate[0] : '';
            $ccExpYear = isset($expirationDate[1]) ? $expirationDate[1] : '';
        }
        $ccExpMonth = $payment->getAdditionalInformation('cc_exp_month') ? : $expMonth;
        $ccExpYear = $payment->getAdditionalInformation('cc_exp_year') ? : $ccExpYear;
        $ccType = $payment->getAdditionalInformation('cc_type') ? : $payment->getType();

        if (
            !$ccLats4
            && !$ccExpMonth
            && !$ccExpYear
            && isset($response['object'])
            && $response['object'] instanceof \Stripe\PaymentIntent
        ) {
            $paymentIntentObject = $response['object'];
            $charges = $paymentIntentObject->__get('charges');
            if ($charges && $charges instanceof \Stripe\Collection) {
                foreach ($charges as $charge) {
                    if ($charge instanceof \Stripe\Charge) {
                        $paymentMethodDetails = $charge
                            ->__get('payment_method_details')
                            ->__get('card');
                        $cardData = $paymentMethodDetails->toArray();
                        $ccLats4 =  isset($cardData['last4']) ? $cardData['last4'] : $ccExpMonth;
                        $ccExpMonth = isset($cardData['exp_month']) ? $cardData['exp_month'] : $ccExpMonth;
                        $ccExpYear =  isset($cardData['exp_year']) ? $cardData['exp_year'] : $ccExpMonth;
                    }
                }
            }
        }
        $ccTypes = $this->typeMapper->getCcTypes();
        if (array_key_exists($ccType, $ccTypes)) {
            $ccType = $ccTypes[$ccType];
        }
        $payment->setCcLast4($ccLats4);
        $payment->setCcExpMonth($ccExpMonth);
        $payment->setCcExpYear($ccExpYear);
        $payment->setCcType($ccType);
        if ($payment->getAdditionalInformation('risk_level')) {
            $payment->unsAdditionalInformation('risk_level');
        }
        if ($payment->getAdditionalInformation('type')) {
            $payment->unsAdditionalInformation('type');
        }
        if ($payment->getAdditionalInformation('seller_message')) {
            $payment->unsAdditionalInformation('seller_message');
        }
        // set card details to additional info
        $payment->setAdditionalInformation(self::CARD_NUMBER, "xxxx-{$ccLats4}");
        $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $ccType);
    }
}
