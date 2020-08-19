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
namespace TNW\Stripe\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

/**
 * Class DataAssignObserver
 * @package TNW\Stripe\Observer
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    /** additional information key */
    const KEY_ADDITIONAL_DATA = 'additional_data';

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(self::KEY_ADDITIONAL_DATA);

        if (is_array($additionalData)) {
            $paymentInfo = $this->readPaymentModelArgument($observer);

            foreach ($additionalData as $key => $value) {
                if ($key === \Magento\Framework\Api\ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY) {
                    continue;
                }

                $paymentInfo->setAdditionalInformation($key, $value);
            }
        }
    }
}
