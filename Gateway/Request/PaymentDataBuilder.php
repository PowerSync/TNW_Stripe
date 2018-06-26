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

class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;
  
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const DESCRIPTION = 'description';
    const CAPTURE = 'capture';

    /** @var SubjectReader  */
    private $subjectReader;

    /**
     * PaymentDataBuilder constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $subject
     * @return array
     */
    public function build(array $subject)
    {
        $paymentDO = $this->subjectReader->readPayment($subject);
        $order = $paymentDO->getOrder();

        return [
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($subject)),
            self::DESCRIPTION => $order->getOrderIncrementId(),
            self::CURRENCY => $order->getCurrencyCode(),
            self::CAPTURE => false
        ];
    }
}
