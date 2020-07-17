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
namespace TNW\Stripe\Gateway\Http\Client;

/**
 * Transaction Void
 */
class TransactionVoid extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $storeId = isset($data['store_id']) ? $data['store_id'] : null;
        // sending store id and other additional keys are restricted by Stripe API
        unset($data['store_id']);

        return $this->adapterFactory->create($storeId)
            ->void($data['transaction_id']);
    }
}
