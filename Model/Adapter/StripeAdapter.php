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

use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Charge;

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
        return Charge::retrieve($transactionId)
            ->refund(['amount' => $amount]);
    }

    /**
     * @param array $attributes
     * @return array|\Exception|Charge|\Stripe\Error\Card
     */
    public function sale(array $attributes)
    {
        return Charge::create($attributes);
    }

    /**
     * @param string $transactionId
     * @param null $amount
     * @return Charge
     */
    public function capture($transactionId, $amount = null)
    {
        return Charge::retrieve($transactionId)
            ->capture(['amount' => $amount]);
    }

    /**
     * @param string $transactionId
     * @return Charge
     */
    public function void($transactionId)
    {
        return Charge::retrieve($transactionId)
            ->refund();
    }

    /**
     * @param array $attributes
     * @return Customer
     */
    public function customer(array $attributes)
    {
        return Customer::create($attributes);
    }
}
