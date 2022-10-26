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

namespace TNW\Stripe\Api;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Vault\Api\Data\PaymentTokenInterface;

interface StoredCardsManagementInterface
{
    /**
     * @param int $customerId
     * @return PaymentTokenInterface[]
     */
    public function getByCustomerId(int $customerId);

    /**
     * @param string $token
     * @param CustomerInterface $customer
     * @param array $arguments
     * @return bool
     * @throws CouldNotSaveException
     */
    public function save(string $token, CustomerInterface $customer, array $arguments = []);

    /**
     * @param string $hash
     * @param int $customerId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function delete(string $hash, int $customerId);
}
