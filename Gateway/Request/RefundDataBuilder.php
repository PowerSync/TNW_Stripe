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
namespace TNW\Stripe\Gateway\Request;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use TNW\Stripe\Helper\Payment\Formatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;

/**
 * Class RefundDataBuilder
 * @package TNW\Stripe\Gateway\Request
 */
class RefundDataBuilder implements BuilderInterface
{
    use Formatter;

    const TRANSACTION_ID = 'transaction_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * RefundDataBuilder constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        return [
            self::TRANSACTION_ID => $payment->getParentTransactionId(),
            PaymentDataBuilder::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($subject))
        ];
    }
}
