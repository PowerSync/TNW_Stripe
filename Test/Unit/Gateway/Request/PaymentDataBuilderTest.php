<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Request\PaymentDataBuilder;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class PaymentDataBuilderTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentDataBuilder
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

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private $order;

    protected function setUp()
    {
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->method('getPayment')
            ->willReturn($this->payment);

        $this->order = $this->createMock(OrderAdapterInterface::class);

        $this->paymentDO->method('getOrder')
            ->willReturn($this->order);

        $this->builder = new PaymentDataBuilder(new SubjectReader());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [];

        $this->builder->build($buildSubject);
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
            PaymentDataBuilder::AMOUNT  => 1045,
            PaymentDataBuilder::DESCRIPTION => '000000101',
            PaymentDataBuilder::CURRENCY  => 'USD',
            PaymentDataBuilder::CAPTURE  => false,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
            'amount' => 10.4500
        ];

        $this->order->method('getOrderIncrementId')
            ->willReturn('000000101');

        $this->order->method('getCurrencyCode')
            ->willReturn('USD');

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }
}
