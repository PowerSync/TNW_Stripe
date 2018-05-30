<?php
/**
 * TNW_Stripe extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  TNW
 * @package   TNW_Stripe
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */
namespace TNW\Stripe\Gateway\Command;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\Data\TransactionInterface;

class CaptureStrategyCommand implements CommandInterface
{
    /**
     * Stripe authorize and capture command
     */
    const SALE = 'sale';

    /**
     * Stripe capture command
     */
    const CAPTURE = 'capture';

    /**
     * Stripe vault capture command
     */
    const VAULT_CAPTURE = 'vault_capture';

    /**
     * Stripe customer command
     */
    const CUSTOMER = 'customer';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor.
     * @param CommandPoolInterface $commandPool
     * @param TransactionRepositoryInterface $repository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        TransactionRepositoryInterface $repository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SubjectReader $subjectReader
    ) {
        $this->commandPool = $commandPool;
        $this->transactionRepository = $repository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $commandSubject
     * @return Command\ResultInterface|null|void
     * @throws Command\CommandException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($commandSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $threeDs = $payment->getAdditionalInformation('cc_3ds');
        $tokenEnabler = $payment->getAdditionalInformation('is_active_payment_token_enabler');

        if ($tokenEnabler && !$threeDs) {
            $this->commandPool->get(self::CUSTOMER)->execute($commandSubject);
        }

        $command = $this->getCommand($payment);
        $this->commandPool->get($command)->execute($commandSubject);

        if ($tokenEnabler && $threeDs) {
            $this->commandPool->get(self::CUSTOMER)->execute($commandSubject);
        }
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return string
     */
    private function getCommand(OrderPaymentInterface $payment)
    {
        $existsCapture = $this->isExistsCaptureTransaction($payment);
        if (!$existsCapture && !$payment->getAuthorizationTransaction()) {
            return self::SALE;
        }

        if (!$existsCapture) {
            return self::CAPTURE;
        }

        // process capture for payment via Vault
        return self::VAULT_CAPTURE;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    private function isExistsCaptureTransaction(OrderPaymentInterface $payment)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('payment_id', $payment->getId())
            ->addFilter('txn_type', TransactionInterface::TYPE_CAPTURE)
            ->create();

        $count = $this->transactionRepository
            ->getList($searchCriteria)
            ->getTotalCount();

        return (boolean) $count;
    }
}
