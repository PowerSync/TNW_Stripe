<?php
namespace TNW\Stripe\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class Customer.
 * Updates stripe_id customer attribute.
 */
class Customer
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Customer constructor.
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param $email
     * @param $cs
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function updateCustomerStripeId($email, $cs)
    {
        try {
            $customer = $this->customerRepository->get($email);
            $customer->setCustomAttribute('stripe_id', $cs);
            $this->customerRepository->save($customer);
        } catch (\Exception $exception) {
            unset($exception);
        }
    }
}
