<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Request\TokenDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtension;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\PaymentToken;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class TokenDataBuilderTest extends \PHPUnit\Framework\TestCase
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
     * @var TokenDataBuilder
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

        $this->builder = new TokenDataBuilder(new SubjectReader());
    }

    /**
     * @covers \TNW\Stripe\Gateway\Request\TokenDataBuilder::build
     */
    public function testBuildCustomerToken()
    {
        $expected = [
            TokenDataBuilder::CUSTOMER => '5tfm4c'
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $paymentExtension = $this->getMockBuilder(OrderPaymentExtension::class)
            ->setMethods(['getVaultPaymentToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $paymentToken = $this->getMockBuilder(PaymentToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentExtension->method('getVaultPaymentToken')
            ->willReturn($paymentToken);

        $this->payment->method('getExtensionAttributes')
            ->willReturn($paymentExtension);

        $paymentToken->method('getGatewayToken')
            ->willReturn('5tfm4c');

        $this->payment->method('getAdditionalInformation')
            ->with('cc_token')
            ->willReturn(null);

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }

    /**
     * @covers \TNW\Stripe\Gateway\Request\TokenDataBuilder::build
     */
    public function testBuildSourceToken()
    {
        $expected = [
            TokenDataBuilder::SOURCE => '5tfm4c'
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $paymentExtension = $this->getMockBuilder(OrderPaymentExtension::class)
            ->setMethods(['getVaultPaymentToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $paymentExtension->method('getVaultPaymentToken')
            ->willReturn(null);

        $this->payment->method('getExtensionAttributes')
            ->willReturn($paymentExtension);

        $this->payment->method('getAdditionalInformation')
            ->with('cc_token')
            ->willReturn('5tfm4c');

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }
}
