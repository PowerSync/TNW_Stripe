<?php

namespace TNW\Stripe\Model\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use TNW\Stripe\Helper\Customer as CustomerHelper;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Stripe\Helper\Payment\Formatter;

/**
 * Class Guest
 * Guest orders actializer in Stripe.
 */
class Guest
{
    use Formatter;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var StripeAdapterFactory
     */
    private $adapterFactory;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * Guest constructor.
     * @param CollectionFactory $collectionFactory
     * @param StripeAdapterFactory $adapterFactory
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StripeAdapterFactory $adapterFactory,
        CustomerHelper $customerHelper
    ) {
        $this->orderCollectionFactory = $collectionFactory;
        $this->adapterFactory = $adapterFactory;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @return Collection
     */
    public function getGuestOrders()
    {
        return $this->orderCollectionFactory
            ->create()
            ->addFieldToSelect('*')
            ->addAttributeToFilter('customer_is_guest', ['eq' => 1])
            ->addAttributeToFilter('guest_order_exported', ['null' => null]);
    }

    /**
     * Export Guest Orders
     *
     * @return array
     */
    public function exportGuestOrders()
    {
        $response = [];
        foreach ($this->getGuestOrders() as $guestOrder) {
            $response[] = $this->export($guestOrder);
        }
        return $response;
    }

    /**
     * @param \Magento\Sales\Model\Order $guestOrder
     * @return mixed
     */
    public function export($guestOrder)
    {
        $response = [];
        try {
            $attributes = [
                'metadata' => ['site' => $guestOrder->getStore()->getBaseUrl()]
            ];

            $attributes['email'] = $guestOrder->getCustomerEmail();
            $attributes['description'] = 'guest';

            $stripeAdapter = $this->adapterFactory->create();
            $cs = $stripeAdapter->customer($attributes);

            $transactionId = $guestOrder->getPayment()->getCcTransId();
            $paymentIntent = $stripeAdapter->retrievePaymentIntent($transactionId);

            $stripeAdapter->retrievePaymentIntent($paymentIntent->id);
            $cid = $paymentIntent->customer;
            $arrayParams = [
                'email' => $guestOrder->getCustomerEmail(),
            ];
            $stripeAdapter->updateCustomer($cid, $arrayParams);
            $guestOrder->setGuestOrderExported(1)->save();
        } catch (\Exception $e) {
            $response[] = ['error' => ['message' => $e->getMessage()]];
        }
        return $response;
    }
}
