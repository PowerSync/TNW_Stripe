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
namespace TNW\Stripe\Model\Checks;

use DateTimeZone;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken\Collection;
use TNW\Stripe\Model\Ui\ConfigProvider;

/**
 * Model class for customer's saved credit cards presence check.
 */
class HasActiveSavedCreditCards implements SpecificationInterface
{
    /**
     * @var Collection
     */
    private $paymentTokenCollection;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @param Collection $paymentTokenCollection
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        Collection $paymentTokenCollection,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->paymentTokenCollection = $paymentTokenCollection;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote)
    {
        if ($paymentMethod->getCode() !== ConfigProvider::CC_VAULT_CODE) {
            return true;
        }

        $customerId = $quote->getCustomerId();

        return $customerId !== null && $this->hasActiveSavedCards($customerId);
    }

    /**
     * Checks if customer has active saved credit cards.
     *
     * @param int $customerId
     * @return bool
     */
    private function hasActiveSavedCards($customerId)
    {
        $this->paymentTokenCollection->addFilter(PaymentTokenInterface::CUSTOMER_ID, $customerId);
        $this->paymentTokenCollection
            ->addFilter(PaymentTokenInterface::PAYMENT_METHOD_CODE, ConfigProvider::CODE);
        $this->paymentTokenCollection->addFilter(PaymentTokenInterface::IS_ACTIVE, 1);
        $this->paymentTokenCollection->addFilter(PaymentTokenInterface::IS_VISIBLE, 1);
        $this->paymentTokenCollection->addFieldToFilter(
            PaymentTokenInterface::EXPIRES_AT,
            [
                'gt' => $this->dateTimeFactory->create(
                    'now',
                    new DateTimeZone('UTC')
                )->format('Y-m-d 00:00:00')
            ]
        );
        return $this->paymentTokenCollection->count() > 0;
    }
}
