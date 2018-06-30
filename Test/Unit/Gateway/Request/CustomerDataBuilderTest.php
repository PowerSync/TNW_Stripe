<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Request\CustomerDataBuilder;
use Magento\Sales\Model\Order;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CustomerDataBuilderTest extends \PHPUnit\Framework\TestCase
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
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var CustomerDataBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->paymentDO = $this->createMock(PaymentDataObjectInterface::class);

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalInformation', 'getOrder'])
            ->getMock();

        $this->paymentDO->method('getPayment')
            ->willReturn($this->payment);

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerEmail'])
            ->getMock();

        $this->payment->method('getOrder')
            ->willReturn($this->order);

        $this->builder = new CustomerDataBuilder(new SubjectReader());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [
            'payment' => null,
        ];

        $this->builder->build($buildSubject);
    }

    /**
     *
     */
    public function testBuild()
    {
        $email = 'john@magento.com';
        $source = 'cus_source';

        $this->payment->method('getAdditionalInformation')
            ->with('cc_token')
            ->willReturn($source);

        $this->order->method('getCustomerEmail')
            ->willReturn($email);

        $expected = [
            'email' => $email,
            'source' => $source
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }
}
