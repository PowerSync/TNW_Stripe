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
namespace TNW\Stripe\Gateway\Helper;

use Magento\Payment\Gateway\Helper;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class SubjectReader
 * @package TNW\Stripe\Gateway\Helper
 */
class SubjectReader
{
    const TRANSACTION_DESCRIPTION = 'Order #';

    /**
     * @param array $subject
     * @return array
     */
    public function readResponseObject(array $subject)
    {
        $response = Helper\SubjectReader::readResponse($subject);

        if (!isset($response['object']) || !\is_object($response['object'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }

        if ($response['object'] instanceof \Stripe\ErrorObject) {
            return [
                'error' => true,
                'message' => __($response['object']->getMessage())
            ];
        }

        return $response['object']->toArray();
    }

    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    public function readTransaction(array $subject)
    {
        if (!isset($subject['object']) || !\is_object($subject['object'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }

        return $subject['object']->toArray();
    }

    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }

    public function readCustomerId(array $subject)
    {
        if (!isset($subject['customer_id'])) {
            throw new \InvalidArgumentException('The customerId field does not exist');
        }

        return (int) $subject['customer_id'];
    }

    public function getOrderIncrementId(OrderPaymentInterface $payment)
    {
        return SELF::TRANSACTION_DESCRIPTION . $payment->getOrder()->getIncrementId();
    }
}
