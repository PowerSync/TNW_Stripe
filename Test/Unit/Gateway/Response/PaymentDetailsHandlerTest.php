<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Response;

use Stripe\Util\Util;
use TNW\Stripe\Gateway\Response\PaymentDetailsHandler;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class PaymentDetailsHandlerTest
 */
class PaymentDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentDataObject|MockObject
     */
    private $paymentDO;

    /**
     * @var Payment|MockObject
     */
    private $payment;

    /**
     * @var PaymentDetailsHandler
     */
    private $paymentHandler;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockBuilder(PaymentDataObject::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCcTransId',
                'setLastTransId',
                'setAdditionalInformation',
                'setIsTransactionPending',
            ])
            ->getMock();

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->payment);

        $this->paymentHandler = new PaymentDetailsHandler(new SubjectReader());
    }

    /**
     * @covers \Magento\Braintree\Gateway\Response\PaymentDetailsHandler::handle
     */
    public function testHandle()
    {
        $subject = [
            'payment' => $this->paymentDO
        ];

        $attributes = [
            'id' => 'ch_1ChBgQ2eZvKYlo2CrbAbA4ol',
            'object' => 'charge',
            'status' => 'succeeded',
            'outcome' => [
                'network_status' => 'approved_by_network',
                'risk_level' => 'normal',
                'seller_message' => 'Payment complete.',
                'type' => 'authorized',
                'reason' => null,
            ],
        ];

        $response = [
            'object' => Util::convertToStripeObject($attributes, [])
        ];

        $this->payment->expects(static::once())
            ->method('setIsTransactionPending')
            ->with(false);

        $this->payment->expects(static::once())
            ->method('setCcTransId')
            ->with('ch_1ChBgQ2eZvKYlo2CrbAbA4ol');

        $this->payment->expects(static::once())
            ->method('setLastTransId')
            ->with('ch_1ChBgQ2eZvKYlo2CrbAbA4ol');

        $this->payment
            ->method('setAdditionalInformation')
            ->withConsecutive(
                ['risk_level', 'normal'],
                ['seller_message', 'Payment complete.'],
                ['type', 'authorized']
            );

        $this->paymentHandler->handle($subject, $response);
    }
}
