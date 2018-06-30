<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Request\CaptureDataBuilder;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Test CaptureDataBuilder
 */
class CaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CaptureDataBuilder
     */
    private $builder;

    /**
     * @var Payment|MockObject
     */
    private $payment;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDO;

    protected function setUp()
    {
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCcTransId'])
            ->getMock();

        $this->paymentDO->method('getPayment')
            ->willReturn($this->payment);

        $this->builder = new CaptureDataBuilder(new SubjectReader());
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage No Authorization Transaction to capture
     */
    public function testBuildWithException()
    {
        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 10.00
        ];

        $this->payment->method('getCcTransId')
            ->willReturn('');

        $this->builder->build($buildSubject);
    }

    public function testBuild()
    {
        $transactionId = 'b3b99d';

        $expected = [
            'transaction_id' => $transactionId,
            'amount' => 1013
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 10.1300
        ];

        $this->payment->method('getCcTransId')
            ->willReturn($transactionId);

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }
}
