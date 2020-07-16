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
namespace TNW\Stripe\Gateway\Response;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use TNW\Stripe\Gateway\Config\Config;
use Magento\Vault\Model\PaymentTokenManagement;

/**
 * Class VaultDetailsHandler
 * @package TNW\Stripe\Gateway\Response
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * VaultDetailsHandler constructor.
     * @param PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param SubjectReader $subjectReader
     * @param Config $config
     * @param PaymentTokenManagement $paymentTokenManagement
     */
    public function __construct(
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        SubjectReader $subjectReader,
        Config $config,
        PaymentTokenManagement $paymentTokenManagement
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();

        $paymentToken = $this->getVaultPaymentToken($transaction, $payment);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * @param $transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    private function getVaultPaymentToken($transaction, $payment)
    {
        // Check token existing in gateway response
        if (!isset($transaction['id'])) {
            return null;
        }
        $paymentToken = null;
        if (
            $payment->getAdditionalInformation('public_hash')
            && $payment->getAdditionalInformation('customer_id')
        ) {
            $paymentToken = $this->paymentTokenManagement->getByPublicHash(
                $payment->getAdditionalInformation('public_hash'),
                $payment->getAdditionalInformation('customer_id')
            );
        }

        if (!$paymentToken) {
            /** @var \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenFactory->create();
            $paymentToken->setGatewayToken($transaction['id']);
            $paymentToken->setExpiresAt($this->getExpirationDate($payment));

            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'type' => $this->getCreditCardType($payment->getAdditionalInformation('cc_type')),
                'maskedCC' => $payment->getAdditionalInformation('cc_last4'),
                'expirationDate' => "{$payment->getAdditionalInformation('cc_exp_month')}/{$payment->getAdditionalInformation('cc_exp_year')}"
            ]));
        }
        if ($paymentToken) {
            $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
            $payment->setAdditionalInformation('cc_type', $tokenDetails['type']);
            $paymentCcNumber = $payment->getAdditionalInformation('cc_number');
            $payment->setAdditionalInformation('cc_number', $paymentCcNumber . $tokenDetails['maskedCC']);
        }
        return $paymentToken;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return string
     */
    private function getExpirationDate($payment)
    {
        $year = $payment->getAdditionalInformation('cc_exp_year');
        $month = $payment->getAdditionalInformation('cc_exp_month');

        return date_create("{$year}-{$month}-01 00:00:00", timezone_open('UTC'))
            ->modify('+ 1 month')
            ->format('Y-m-d 00:00:00');
    }

    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

    /**
     * Get type of credit card mapped from Stripe
     *
     * @param string $type
     * @return string
     */
    private function getCreditCardType($type)
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCctypesMapper();

        if (empty($mapper[$replaced])) {
            return $type;
        }

        return $mapper[$replaced];
    }

    /**
     * Get payment extension attributes
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
