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

use Magento\Framework\App\Area;

/**
 * Transaction Sale
 */
class TransactionSale extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        try {
            $storeId = isset($data['store_id']) ? $data['store_id'] : null;
            // sending store id and other additional keys are restricted by Stripe API
            unset($data['store_id']);
            if (!array_key_exists('payment_method', $data)
                || !array_key_exists('customer', $data)
                || (!$data['payment_method'] && !$data['customer'])
            ) {
                unset($data['shipping']);
            }
            if ($this->getArea() === Area::AREA_ADMINHTML) {
                if ($this->getCurrentUrl() === $this->getAdminOrdersUrl() && !isset($data['customer'])) {
                    unset($data['payment_method']);
                } else {
                    if (isset($data['payment_method'])
                        && $data['payment_method']
                        && !isset($data['set_pm'])
                    ) {
                        unset($data['pi']);
                    }
                    unset($data['source']);
                }
            }
            unset($data['set_pm']);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->debug($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        return $this->adapterFactory->create($storeId)
            ->sale($data);
    }
}
