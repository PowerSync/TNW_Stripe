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
namespace TNW\Stripe\Model\Adapter;

use Magento\Setup\Exception;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\PaymentIntent;

class StripeAdapter
{
    /**
     * StripeAdapter constructor.
     * @param string $secretKey
     */
    public function __construct($secretKey)
    {
        $this->secretKey($secretKey);
    }

    /**
     * @param string|null $value
     */
    public function secretKey($value = null)
    {
        Stripe::setApiKey($value);
    }

    /**
     * @param $transactionId
     * @param null $amount
     * @return Charge
     */
    public function refund($transactionId, $amount = null)
    {
        if (strpos($transactionId, 'ch_') !== false) {
            $chId = $transactionId;
        } else {
            $pi = PaymentIntent::retrieve($transactionId);
            $chId = $pi->charges->data[0]->id;
        }

        if (!$chId) {
            throw new Exception('Charge not found.');
        }
        $ch = Charge::retrieve($chId);
        return $ch->refund(['amount' => $amount]);
    }

    /**
     * @param array $attributes
     * @return array|\Exception|Charge|\Stripe\Error\Card
     */
    public function sale(array $attributes)
    {
        $needCapture = isset($attributes['capture']) ? true : false;
        if ($needCapture) {
            unset($attributes['capture']);
        }
        // Payment with old saved customer
        if (isset($attributes['customer']) && !isset($attributes['payment_method'])) {
            unset($attributes['payment_method_types']);
            unset($attributes['confirmation_method']);
            return Charge::create($attributes);
        }
        if (empty($attributes['pi'])) {
            $pi = PaymentIntent::create($attributes);
        } else {
            $pi = PaymentIntent::retrieve($attributes['pi']);
        }
        if ($needCapture) {
            if ($pi->status == 'requires_confirmation') {
                $pi->confirm();
            }
            $pi->capture(['amount' => $attributes['amount']]);
        }
        return $pi;
    }

    /**
     * @param string $transactionId
     * @param null $amount
     * @return Charge
     */
    public function capture($transactionId, $amount = null)
    {
        $pi = PaymentIntent::retrieve($transactionId);
        if ($pi->status == 'requires_confirmation') {
            $pi->confirm();
        }
        $pi->capture(['amount' => $amount]);
        return $pi;
    }

    /**
     * @param string $transactionId
     * @return Charge
     */
    public function void($transactionId)
    {
        return PaymentIntent::retrieve($transactionId)
            ->cancel();
    }

    /**
     * @param array $attributes
     * @return Customer
     */
    public function customer(array $attributes)
    {
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);
            Customer::update($id, $attributes);
            $cs = Customer::retrieve($id);
            return $cs;
        }
        return Customer::create($attributes);
    }

    /**
     * @param array $attributes
     * @return array|\Exception|Charge|\Stripe\Error\Card
     */
    public function createPaymentIntent (array $attributes)
    {
        return PaymentIntent::create($attributes)->confirm();
    }

    /**
     * @param array $attributes
     * @return array|\Exception|Charge|\Stripe\Error\Card
     */
    public function retrievePaymentIntent ($transactionId)
    {
        return PaymentIntent::retrieve($transactionId);
    }

    /**
     * @param array $attributes
     * @return array|\Exception|Charge|\Stripe\Error\Card
     */
    public function retrieveCustomer ($customerId)
    {
        return Customer::retrieve($customerId);
    }

}
