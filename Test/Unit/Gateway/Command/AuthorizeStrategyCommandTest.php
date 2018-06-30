<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use TNW\Stripe\Gateway\Command\AuthorizeStrategyCommand;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Test AuthorizeStrategyCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuthorizeStrategyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AuthorizeStrategyCommand|MockObject
     */
    private $strategyCommand;

    /**
     * @var CommandPoolInterface|MockObject
     */
    private $commandPool;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReader;

    /**
     * @var GatewayCommand|MockObject
     */
    private $command;

    /**
     * @var Payment|MockObject
     */
    private $payment;

    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', '__wakeup'])
            ->getMock();

        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = $this->getMockBuilder(GatewayCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->command->method('execute')
            ->willReturn([]);

        $this->strategyCommand = new AuthorizeStrategyCommand(
            $this->commandPool,
            $this->subjectReader
        );
    }

    public function testAuthorizeExecute()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $subject['payment'] = $paymentData;

        $this->subjectReader->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);

        $this->commandPool->method('get')
            ->with(AuthorizeStrategyCommand::AUTHORIZE)
            ->willReturn($this->command);

        $this->strategyCommand->execute($subject);
    }

    /**
     * Creates mock for payment data object and order payment
     * @return MockObject
     */
    private function getPaymentDataObjectMock()
    {
        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder(PaymentDataObject::class)
            ->setMethods(['getPayment', 'getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getPayment')
            ->willReturn($this->payment);

        $order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getOrder')
            ->willReturn($order);

        return $mock;
    }
}
