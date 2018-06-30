<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Stripe\Util\Util;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Response\RefundHandler;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class RefundHandlerTest extends \PHPUnit\Framework\TestCase
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
     * @var Creditmemo|MockObject
     */
    private $creditmemo;

    /**
     * @var Invoice|MockObject
     */
    private $invoice;

    /**
     * @var RefundHandler
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
                'getCreditmemo',
                'setTransactionId',
                'setIsTransactionClosed',
                'setShouldCloseParentTransaction',
            ])
            ->getMock();

        $this->paymentDO
            ->method('getPayment')
            ->willReturn($this->payment);

        $this->creditmemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInvoice'])
            ->getMock();

        $this->payment
            ->method('getCreditmemo')
            ->willReturn($this->creditmemo);

        $this->invoice = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->setMethods(['canRefund'])
            ->getMock();

        $this->creditmemo
            ->method('getInvoice')
            ->willReturn($this->invoice);

        $this->handler = new RefundHandler(new SubjectReader());
    }

    /**
     * @covers RefundHandler::handle()
     */
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

        $this->payment->expects(static::never())
            ->method('setTransactionId');

        $this->invoice
            ->method('canRefund')
            ->willReturn(true);

        $this->payment->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(true);
        $this->payment->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);

        $this->handler->handle($handlingSubject, $response);
    }
}
