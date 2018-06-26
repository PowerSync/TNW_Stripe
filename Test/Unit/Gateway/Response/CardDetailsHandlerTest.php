<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Response;

use Stripe\Util\Util;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Stripe\Gateway\Response\CardDetailsHandler;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Test CardDetailsHandler
 */
class CardDetailsHandlerTest extends \PHPUnit\Framework\TestCase
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
     * @var CardDetailsHandler
     */
    private $cardHandler;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockBuilder(PaymentDataObject::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCcLast4',
                'setCcExpMonth',
                'setCcExpYear',
                'setCcType',
                'setAdditionalInformation',
                'getAdditionalInformation',
            ])
            ->getMock();

        $this->payment->method('getAdditionalInformation')
            ->willReturnMap(
                [
                    ['cc_last4', '0001'],
                    ['cc_exp_month', '01'],
                    ['cc_exp_year', '18'],
                    ['cc_type', 'Visa']
                ]
            );

        $this->payment->expects(static::once())
            ->method('setCcLast4')
            ->with('0001');

        $this->payment->expects(static::once())
            ->method('setCcExpMonth')
            ->with('01');

        $this->payment->expects(static::once())
            ->method('setCcExpYear')
            ->with('18');

        $this->payment->expects(static::once())
            ->method('setCcType')
            ->with('Visa');

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->payment);

        $this->cardHandler = new CardDetailsHandler(new SubjectReader());
    }

    /**
     * @covers \TNW\Stripe\Gateway\Response\CardDetailsHandler::handle
     */
    public function testHandleThreeDSecure()
    {
        $subject = [
            'payment' => $this->paymentDO
        ];

        $attributes = [
            'object' => 'charge',
            'paid' => true,
            'status' => 'succeeded',
            'outcome' => [
                'network_status' => 'approved_by_network',
            ],
            'source' => [
                'object' => 'source',
                'type' => 'three_d_secure',
                'three_d_secure' => [
                    'card' => 'card_key'
                ]
            ]
        ];

        $response = [
            'object' => Util::convertToStripeObject($attributes, [])
        ];

        $this->payment->expects(static::exactly(3))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [CardDetailsHandler::CARD_NUMBER, 'xxxx-0001'],
                [OrderPaymentInterface::CC_TYPE, 'Visa'],
                ['cc_token', 'card_key']
            );

        $this->cardHandler->handle($subject, $response);
    }

    /**
     * @covers \TNW\Stripe\Gateway\Response\CardDetailsHandler::handle
     */
    public function testHandleAchCreditTransfer()
    {
        $subject = [
            'payment' => $this->paymentDO
        ];

        $attributes = [
            'object' => 'charge',
            'paid' => true,
            'status' => 'succeeded',
            'outcome' => [
                'network_status' => 'approved_by_network',
            ],
            'source' => [
                'object' => 'source',
                'type' => 'ach_credit_transfer',
            ]
        ];

        $response = [
            'object' => Util::convertToStripeObject($attributes, [])
        ];

        $this->payment->expects(static::exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [CardDetailsHandler::CARD_NUMBER, 'xxxx-0001'],
                [OrderPaymentInterface::CC_TYPE, 'Visa']
            );

        $this->cardHandler->handle($subject, $response);
    }
}
