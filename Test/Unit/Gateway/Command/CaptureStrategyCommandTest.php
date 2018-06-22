<?php
/**
 *
 */
namespace TNW\Stripe\Test\Unit\Gateway\Command;

use TNW\Stripe\Gateway\Command\CaptureStrategyCommand;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Model\Adapter\StripeAdapter;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Test CaptureStrategyCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureStrategyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CaptureStrategyCommand
     */
    private $strategyCommand;

    /**
     * @var CommandPoolInterface|MockObject
     */
    private $commandPool;

    /**
     * @var TransactionRepositoryInterface|MockObject
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var Payment|MockObject
     */
    private $payment;

    /**
     * @var GatewayCommand|MockObject
     */
    private $command;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReader;

    /**
     * @var StripeAdapter|MockObject
     */
    private $stripeAdapter;

    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', '__wakeup'])
            ->getMock();

        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->initCommandMock();
        $this->initTransactionRepositoryMock();
        $this->initSearchCriteriaBuilderMock();

        $this->stripeAdapter = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var StripeAdapterFactory|MockObject $adapterFactory */
        $adapterFactory = $this->getMockBuilder(StripeAdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapterFactory->method('create')
            ->willReturn($this->stripeAdapter);

        $this->strategyCommand = new CaptureStrategyCommand(
            $this->commandPool,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->subjectReader
        );
    }

    /**
     * Creates mock for gateway command object
     */
    private function initCommandMock()
    {
        $this->command = $this->getMockBuilder(GatewayCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->command->method('execute')
            ->willReturn([]);
    }

    /**
     * Create mock for transaction repository
     */
    private function initTransactionRepositoryMock()
    {
        $this->transactionRepository = $this->getMockBuilder(TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'getTotalCount', 'delete', 'get', 'save', 'create', '__wakeup'])
            ->getMock();
    }

    /**
     * Create mock for search criteria object
     */
    private function initSearchCriteriaBuilderMock()
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilters', 'create', '__wakeup'])
            ->getMock();
    }

    public function testSaleExecute()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $subject['payment'] = $paymentData;

        $this->subjectReader->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);

        $this->payment->method('getAuthorizationTransaction')
            ->willReturn(false);

        $this->payment->method('getId')
            ->willReturn(1);

        $this->buildSearchCriteria();

        $this->transactionRepository->method('getTotalCount')
            ->willReturn(0);

        $this->commandPool->method('get')
            ->with(CaptureStrategyCommand::SALE)
            ->willReturn($this->command);

        $this->strategyCommand->execute($subject);
    }

    public function testCaptureExecute()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $subject['payment'] = $paymentData;
        $lastTransId = 'txnds';

        $this->subjectReader->method('readPayment')
            ->with($subject)
            ->willReturn($paymentData);

        $this->payment->method('getAuthorizationTransaction')
            ->willReturn(true);
        $this->payment->method('getLastTransId')
            ->willReturn($lastTransId);

        $this->payment->method('getId')
            ->willReturn(1);

        $this->buildSearchCriteria();

        $this->transactionRepository->method('getTotalCount')
            ->willReturn(0);

        $this->commandPool->method('get')
            ->with(CaptureStrategyCommand::CAPTURE)
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

    /**
     * Builds search criteria
     */
    private function buildSearchCriteria()
    {
        $searchCriteria = new SearchCriteria();
        $this->searchCriteriaBuilder->expects(self::exactly(2))
            ->method('addFilters')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')
            ->willReturn($searchCriteria);

        $this->transactionRepository->method('getList')
            ->with($searchCriteria)
            ->willReturnSelf();
    }
}
