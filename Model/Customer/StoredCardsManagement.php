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

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use TNW\Stripe\Api\StoredCardsManagementInterface;
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
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
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
    public function save(InfoInterface $payment, CustomerInterface $customer, array $arguments = [])
    {
        // TODO: Implement save() method.
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
}
