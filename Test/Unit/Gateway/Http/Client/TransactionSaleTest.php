<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Http\Client;

use TNW\Stripe\Gateway\Http\Client\TransactionSale;
use TNW\Stripe\Model\Adapter\StripeAdapter;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Log\LoggerInterface;
use Stripe\StripeObject;

/**
 * Test TransactionSale
 */
class TransactionSaleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TransactionSale
     */
    private $model;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var LoggerInterface|MockObject
     */
    private $criticalLogger;

    /**
     * @var StripeAdapter|MockObject
     */
    private $adapter;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->criticalLogger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['sale'])
            ->getMock();

        /** @var StripeAdapterFactory|MockObject $adapterFactory */
        $adapterFactory = $this->getMockBuilder(StripeAdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapterFactory->method('create')
            ->with('test-store-id')
            ->willReturn($this->adapter);

        $this->model = new TransactionSale($this->criticalLogger, $this->logger, $adapterFactory);
    }

    /**
     * Runs test placeRequest method (exception)
     *
     * @return void
     *
     * @expectedException \Magento\Payment\Gateway\Http\ClientException
     * @expectedExceptionMessage Test messages
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function testPlaceRequestException()
    {
        $this->logger->method('debug')
            ->with([
                'request' => $this->getTransferData(),
                'client' => TransactionSale::class,
                'response' => []
            ]);

        $this->criticalLogger->method('critical')
            ->with('Test messages');

        $this->adapter->method('sale')
            ->with(['test-data-key' => 'test-data-value'])
            ->willThrowException(new \Exception('Test messages'));

        /** @var TransferInterface|MockObject $transferObjectMock */
        $transferObjectMock = $this->getTransferObjectMock();

        $this->model->placeRequest($transferObjectMock);
    }

    /**
     * Run test placeRequest method
     *
     * @return void
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function testPlaceRequestSuccess()
    {
        $response = $this->getResponseObject();

        $this->adapter->method('sale')
            ->with(['test-data-key' => 'test-data-value'])
            ->willReturn($response);

        $this->logger->method('debug')
            ->with([
                'request' => $this->getTransferData(),
                'client' => TransactionSale::class,
                'response' => ['success' => 1]
            ]);

        $actualResult = $this->model->placeRequest($this->getTransferObjectMock());

        $this->assertInternalType('object', $actualResult['object']);
        $this->assertEquals(['object' => $response], $actualResult);
    }

    /**
     * Creates mock object for TransferInterface.
     *
     * @return TransferInterface|MockObject
     */
    private function getTransferObjectMock()
    {
        $transferObjectMock = $this->createMock(TransferInterface::class);
        $transferObjectMock->method('getBody')
            ->willReturn($this->getTransferData());

        return $transferObjectMock;
    }

    /**
     * Creates stub for a response.
     *
     * @return StripeObject
     */
    private function getResponseObject()
    {
        $obj = new StripeObject();
        $obj['success'] = true;

        return $obj;
    }

    /**
     * Creates stub request data.
     *
     * @return array
     */
    private function getTransferData()
    {
        return [
            'store_id' => 'test-store-id',
            'test-data-key' => 'test-data-value'
        ];
    }
}
