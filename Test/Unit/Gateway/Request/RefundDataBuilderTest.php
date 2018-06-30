<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Request\PaymentDataBuilder;
use TNW\Stripe\Gateway\Request\RefundDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class RefundDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDO;

    /**
     * @var Payment|MockObject
     */
    private $payment;

    /**
     * @var RefundDataBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->method('getPayment')
            ->willReturn($this->payment);

        $this->builder = new RefundDataBuilder(new SubjectReader());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildReadAmountException()
    {
        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => null
        ];

        $this->builder->build($buildSubject);
    }

    public function testBuild()
    {
        $expected = [
            RefundDataBuilder::TRANSACTION_ID => 'xsd7n',
            PaymentDataBuilder::AMOUNT => '1236'
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 12.3580
        ];

        $this->payment->method('getParentTransactionId')
            ->willReturn('xsd7n');

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }
}
