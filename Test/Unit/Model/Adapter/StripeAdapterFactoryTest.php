<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Model\Adapter;

use Magento\Framework\ObjectManagerInterface;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Model\Adapter\StripeAdapter;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * StripeAdapterFactory Test
 */
class StripeAdapterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManager;

    /**
     * @var StripeAdapterFactory
     */
    private $adapterFactory;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSecretKey'])
            ->getMock();

        $this->objectManager = $this->createMock(ObjectManagerInterface::class);

        $this->adapterFactory = new StripeAdapterFactory(
            $this->objectManager,
            $this->config
        );
    }

    /**
     * @covers StripeAdapterFactory::create()
     */
    public function testCreate()
    {
        $expected = $this->createMock(StripeAdapter::class);

        $this->objectManager
            ->method('create')
            ->with(StripeAdapter::class, ['secretKey' => 'test_secret_key'])
            ->willReturn($expected);

        $this->config
            ->method('getSecretKey')
            ->with(5)
            ->willReturn('test_secret_key');

        self::assertEquals($expected, $this->adapterFactory->create(5));
    }
}