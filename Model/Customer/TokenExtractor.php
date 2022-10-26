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
 * @copyright Copyright (c) 2017-2022
 * @license   Open Software License (OSL 3.0)
 */
namespace TNW\Stripe\Model\Customer;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use TNW\Stripe\Gateway\Http\Client\TransactionCustomer;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Gateway\Http\TransferFactory;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Framework\Encryption\EncryptorInterface;
use TNW\Stripe\Model\Ui\ConfigProvider;

/**
 * Class TokenExtractor - stripe
 */
class TokenExtractor
{
    /**
     * @var CreditCardTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var mixed
     */
    private $subjectReader;

    /**
     * @var mixed
     */
    private $transferFactory;

    /**
     * @var mixed
     */
    private $client;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var mixed
     */
    private $gatewayConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param SerializerInterface $serializer
     * @param TransactionCustomer $client
     * @param Config $gatewayConfig
     * @param TransferFactory $transferFactory
     * @param SubjectReader $subjectReader
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        CreditCardTokenFactory $creditCardTokenFactory,
        SerializerInterface $serializer,
        TransactionCustomer $client,
        Config $gatewayConfig,
        TransferFactory $transferFactory,
        SubjectReader $subjectReader,
        EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
        $this->paymentTokenFactory = $creditCardTokenFactory;
        $this->tokenManagement = $tokenManagement;
        $this->client = $client;
        $this->gatewayConfig = $gatewayConfig;
        $this->transferFactory = $transferFactory;
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer;
    }

    /**
     * @param $response
     * @param $customer
     * @param $paymentData
     * @return array
     */
    public function getPaymentTokenWithTransactionId($response, $customer, $paymentData)
    {
        $transactionAuth = $this->subjectReader->readTransaction($response);
        return [
            'payment_token' => $this->getVaultPaymentToken($paymentData, $transactionAuth, $customer->getId()),
            'transaction_id' => $transactionAuth['id']
        ];
    }

    /**
     * @param $paymentData
     * @param $transaction
     * @param int $customerId
     * @return PaymentTokenInterface|null
     */
    private function getVaultPaymentToken($paymentData, $transaction, $customerId = 0)
    {
        $gateWayToken = $transaction['customer'] . '/' . $transaction['payment_method'];

        if (!$paymentToken = $this->tokenManagement->getByGatewayToken(
            $gateWayToken,
            'tnw_stripe',
            $customerId
        )) {
            $last4 = isset($paymentData['last4']) ? $paymentData['last4'] : '';
            /** @var PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenFactory->create()
                ->setExpiresAt($this->getExpirationDate($paymentData))
                ->setGatewayToken($gateWayToken);
            $ccMap = $this->gatewayConfig->getCcTypesMapper();
            $transactionCharges = $transaction['charges'];
            if (is_array($transactionCharges) && isset($transactionCharges['data']) && !$last4) {
                $last4 = $transactionCharges['data'][0]['payment_method_details']['card']['last4'];
            } elseif (!$last4) {
                foreach ($transactionCharges as $charge) {
                    $details = $charge->__get('payment_method_details');
                    $cardData = $details->__get('card');
                    $last4 = $cardData->__get('last4');
                }
            }
            $paymentToken
                ->setTokenDetails($this->convertDetailsToJSON([
                    'type' => $ccMap[$paymentData['type']],
                    'maskedCC' => $last4,
                    'expirationDate' => sprintf(
                        '%s/%s',
                        $paymentData['exp_month'],
                        $paymentData['exp_year']
                    )
                ]))
                ->setCustomerId($customerId)
                ->setPaymentMethodCode(ConfigProvider::CODE)
                ->setPublicHash($this->generatePublicHash($paymentToken));
        }

        return $paymentToken;
    }

    /**
     * @param $payment
     * @return string
     */
    private function getExpirationDate($payment)
    {
        $time = sprintf(
            '%s-%s-01 00:00:00',
            trim($payment['exp_year']),
            trim($payment['exp_month'])
        );

        return date_create($time, timezone_open('UTC'))
            ->modify('+1 month')
            ->format('Y-m-d 00:00:00');
    }

    /**
     * @param $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }


    /**
     * @param $paymentToken
     * @return string
     */
    private function generatePublicHash($paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }
}
