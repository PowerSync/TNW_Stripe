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
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;

class AuthorizeStrategyCommand implements CommandInterface
{
    /**
     * Stripe authorize command
     */
    const AUTHORIZE = 'authorize';

    /**
     * Stripe customer command
     */
    const CUSTOMER = 'customer';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor.
     * @param CommandPoolInterface $commandPool
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        SubjectReader $subjectReader
    ) {
        $this->commandPool = $commandPool;
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
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $threeDs = $payment->getAdditionalInformation('cc_3ds');
        $tokenEnabler = $payment->getAdditionalInformation('is_active_payment_token_enabler');

        if ($tokenEnabler && !$threeDs) {
            $this->commandPool->get(self::CUSTOMER)->execute($commandSubject);
        }

        $this->commandPool->get(self::AUTHORIZE)->execute($commandSubject);

        if ($tokenEnabler && $threeDs) {
            $this->commandPool->get(self::CUSTOMER)->execute($commandSubject);
        }
    }
}
