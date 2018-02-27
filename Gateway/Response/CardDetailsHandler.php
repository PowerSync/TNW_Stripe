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

use TNW\Stripe\Gateway\Config\Config;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CardDetailsHandler implements HandlerInterface
{
    const CARD_TYPE = 'brand';
    const CARD_EXP_MONTH = 'exp_month';
    const CARD_EXP_YEAR = 'exp_year';
    const CARD_LAST4 = 'last4';

    private $config;
    private $subjectReader;

    public function __construct(
        Config $config,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    public function handle(array $subject, array $response)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        /** @var \Stripe\Source $source */
        $source = $transaction['source'];

        $payment->setCcLast4($source->card[self::CARD_LAST4]);
        $payment->setCcExpMonth($source->card[self::CARD_EXP_MONTH]);
        $payment->setCcExpYear($source->card[self::CARD_EXP_YEAR]);
        $payment->setCcType($source->card[self::CARD_TYPE]);
    }
}
