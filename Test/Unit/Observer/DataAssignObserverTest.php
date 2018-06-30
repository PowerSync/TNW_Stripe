<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use TNW\Stripe\Observer\DataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class DataAssignObserverTest
 */
class DataAssignObserverTest extends \PHPUnit\Framework\TestCase
{
    const PAYMENT_METHOD_NONCE = 'nonce';
    const DEVICE_DATA = '{"test": "test"}';

    /**
     * @var Event\Observer|MockObject
     */
    private $observerContainer;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var DataAssignObserver
     */
    private $observer;

    protected function setUp()
    {
        $this->observerContainer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->observer = new DataAssignObserver();
    }

    public function testExecute()
    {
        $paymentInfoModel = $this->createMock(InfoInterface::class);
        $dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'payment_method_nonce' => self::PAYMENT_METHOD_NONCE,
                    'device_data' => self::DEVICE_DATA
                ]
            ]
        );

        $this->event->expects(static::exactly(2))
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [AbstractDataAssignObserver::MODEL_CODE, $paymentInfoModel],
                    [AbstractDataAssignObserver::DATA_CODE, $dataObject]
                ]
            );

        $paymentInfoModel->expects(static::at(0))
            ->method('setAdditionalInformation')
            ->with('payment_method_nonce', self::PAYMENT_METHOD_NONCE);

        $paymentInfoModel->expects(static::at(1))
            ->method('setAdditionalInformation')
            ->with('device_data', self::DEVICE_DATA);

        $this->observer->execute($this->observerContainer);
    }
}
