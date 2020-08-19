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

use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class PaymentDetailsHandler
 * @package TNW\Stripe\Gateway\Response
 */
class PaymentDetailsHandler implements HandlerInterface
{
    const RISK_LEVEL = 'risk_level';
    const SELLER_MESSAGE = 'seller_message';
    const CAPTURE = 'captured';
    const TYPE = 'type';

    /**
     * @var array
     */
    private $additionalInformationMapping = [
        self::RISK_LEVEL,
        self::SELLER_MESSAGE,
        self::CAPTURE,
        self::TYPE
    ];

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * PaymentDetailsHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $subject
     * @param array $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $subject, array $response)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $transaction = $this->subjectReader->readTransaction($response);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $payment->setIsTransactionPending(strcasecmp($transaction['status'], 'pending') === 0);
        if (!empty($transaction['fraud_details']['stripe_report'])) {
            $payment->setIsFraudDetected(
                strcasecmp($transaction['fraud_details']['stripe_report'], 'fraudulent') === 0
            );
        }

        $payment->setCcTransId($transaction['id']);
        $payment->setLastTransId($transaction['id']);

        $outcome = isset($transaction['charges']['data'][0]['outcome'])
            ? $transaction['charges']['data'][0]['outcome']
            : [];

        //remove previously set payment token
        //$payment->unsAdditionalInformation('cc_token');
        foreach ($this->additionalInformationMapping as $item) {
            if (!isset($outcome[$item])) {
                continue;
            }
            $payment->setAdditionalInformation($item, $outcome[$item]);
        }
    }
}
