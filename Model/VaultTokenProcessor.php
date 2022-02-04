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
namespace TNW\Stripe\Model;

use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use Stripe\Collection;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Used to retrieve the customer and payment method from payment token
 */
class VaultTokenProcessor
{
    /**
     * @var StripeAdapterFactory
     */
    private $adapterFactory;

    /**
     * @param StripeAdapterFactory $adapterFactory
     */
    public function __construct(
        StripeAdapterFactory $adapterFactory
    ) {
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * @param PaymentTokenInterface $paymentToken
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getPaymentMethodByVaultToken(PaymentTokenInterface $paymentToken)
    {
        $gatewayToken = $paymentToken->getGatewayToken();
        $compositeGatewayToken = explode('/', $gatewayToken);
        if (count($compositeGatewayToken) > 1) {
            $customer = $compositeGatewayToken[0];
            $paymentMethod = $compositeGatewayToken[1];
        } else {
            $customer = $paymentToken->getGatewayToken();
            $stripeAdapter = $this->adapterFactory->create();
            $customerPaymentMethods = $stripeAdapter->retrieveCustomerPaymentMethods($customer);
            $paymentMethod = $this->retrieveCurrentPaymentMethodId(
                $customerPaymentMethods,
                $paymentToken
            );
        }
        return [$paymentMethod, $customer];
    }

    /**
     * @param $customerPaymentMethods
     * @param $paymentToken
     * @return mixed|string
     */
    private function retrieveCurrentPaymentMethodId($customerPaymentMethods, $paymentToken)
    {
        $result = '';
        if ($customerPaymentMethods instanceof Collection) {
            $customerPaymentMethods = $customerPaymentMethods->toArray();
            if (array_key_exists('data', $customerPaymentMethods)
                && is_array($customerPaymentMethods['data'])) {
                $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
                list($expirationMonth, $expirationYear) = explode('/', $tokenDetails['expirationDate']);
                foreach ($customerPaymentMethods['data'] as $paymentMethod) {
                    if ($paymentMethod['card']['last4'] == $tokenDetails['maskedCC']
                        && $paymentMethod['card']['exp_month'] == $expirationMonth
                        && $paymentMethod['card']['exp_year'] == $expirationYear
                    ) {
                        $result = $paymentMethod['id'];
                        break;
                    }
                }
            }
        }
        return $result;
    }
}
