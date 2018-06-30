<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Request\VoidDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class VoidDataBuilderTest extends \PHPUnit\Framework\TestCase
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
     * @var VoidDataBuilder
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

        $this->builder = new VoidDataBuilder(new SubjectReader());
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
     * @param $parentTransactionId
     * @param $lastTransId
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild($parentTransactionId, $lastTransId)
    {
        $expected = [
            'transaction_id' => 'xsd7n',
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->payment->method('getParentTransactionId')
            ->willReturn($parentTransactionId);

        $this->payment->method('getLastTransId')
            ->willReturn($lastTransId);

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }

    /**
     * @return array
     */
    public function dataProviderBuild()
    {
        return [
            ['xsd7n', null],
            [null, 'xsd7n']
        ];
    }
}
