<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Response;

use Stripe\Util\Util;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Response\TransactionIdHandler;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class TransactionIdHandlerTest extends \PHPUnit\Framework\TestCase
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
     * @var TransactionIdHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockBuilder(PaymentDataObject::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setTransactionId',
                'setIsTransactionClosed',
                'setShouldCloseParentTransaction',
            ])
            ->getMock();

        $this->paymentDO
            ->method('getPayment')
            ->willReturn($this->payment);

        $this->handler = new TransactionIdHandler(new SubjectReader());
    }

    public function testHandle()
    {
        $handlingSubject = [
            'payment' => $this->paymentDO
        ];

        $attributes = [
            'id' => 'ch_1ChBgQ2eZvKYlo2CrbAbA4ol',
            'object' => 'charge',
        ];

        $response = [
            'object' => Util::convertToStripeObject($attributes, [])
        ];

        $this->payment->expects(static::once())
            ->method('setTransactionId')
            ->with('ch_1ChBgQ2eZvKYlo2CrbAbA4ol');

        $this->payment->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false);

        $this->payment->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);

        $this->handler->handle($handlingSubject, $response);
    }
}
