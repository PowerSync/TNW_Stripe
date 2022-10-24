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

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use TNW\Stripe\Api\StoredCardsManagementInterface;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Stripe\Model\Ui\ConfigProvider;

class StoredCardsManagement implements StoredCardsManagementInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var StripeAdapterFactory
     */
    private $adapterFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param StripeAdapterFactory $adapterFactory
     * @param UrlInterface $url
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param SerializerInterface $serializer
     * @param Config $gatewayConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        StripeAdapterFactory $adapterFactory,
        UrlInterface $url,
        PaymentTokenFactory $paymentTokenFactory,
        SerializerInterface $serializer,
        Config $gatewayConfig,
        EncryptorInterface $encryptor
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->adapterFactory = $adapterFactory;
        $this->url = $url;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->serializer = $serializer;
        $this->gatewayConfig = $gatewayConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId(int $customerId)
    {
        $storedCards = [];
        foreach ($this->paymentTokenManagement->getVisibleAvailableTokens($customerId) as $token) {
            if ($token->getPaymentMethodCode() === ConfigProvider::CODE) {
                $storedCards[] = $token;
            }
        }
        return $storedCards;
    }

    /**
     * @inheritDoc
     */
    public function save($token, CustomerInterface $customer, array $arguments = [])
    {
        $stripeAdapter = $this->adapterFactory->create();
        try {
            $paymentIntent = $stripeAdapter->retrievePaymentIntent($token);
            $cs = $stripeAdapter->customer([
                'email' => $customer->getEmail(),
                'payment_method' => $token,
                'invoice_settings' => ['default_payment_method' => $token],
                'metadata' => ['site' => $this->url->getBaseUrl()]
            ]);

            $gateWayToken = $cs->id . '/' . $paymentIntent->payment_method;
            $paymentToken = $this->paymentTokenFactory->create()
                ->setExpiresAt($this->getExpirationDate($arguments['exp_year'], $arguments['exp_month']))
                ->setGatewayToken($gateWayToken)
                ->setType('card');

            $ccMap = $this->gatewayConfig->getCcTypesMapper();

            $paymentToken->setTokenDetails(
                $this->serializer->serialize([
                    'type' => $ccMap[$arguments['type']],
                    'maskedCC' => $arguments['last4'],
                    'expirationDate' => sprintf('%s/%s', $arguments['exp_month'], $arguments['exp_year'])
                ])
            );
            $paymentToken->setPublicHash($this->generatePublicHash($paymentToken))
                ->setCustomerId($customer->getId())
                ->setPaymentMethodCode(ConfigProvider::CODE);
            $this->paymentTokenRepository->save($paymentToken);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $hash, int $customerId)
    {
        $paymentToken = $this->paymentTokenManagement->getByPublicHash($hash, $customerId);
        if (!$paymentToken) {
            throw new NoSuchEntityException(__('Payment Token does not exist.'));
        }
        return $this->paymentTokenRepository->delete($paymentToken);
    }

    /**
     * @param $paymentToken
     * @return string
     */
    protected function generatePublicHash($paymentToken)
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

    /**
     * @param $expYear
     * @param $expMonth
     * @return string
     */
    private function getExpirationDate($expYear, $expMonth)
    {
        $time = sprintf('%s-%s-01 00:00:00', $expYear, $expMonth);

        return date_create($time, timezone_open('UTC'))
            ->modify('+1 month')
            ->format('Y-m-d 00:00:00');
    }
}
