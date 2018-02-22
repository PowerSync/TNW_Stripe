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
namespace TNW\Stripe\Gateway\Config;

use TNW\Stripe\Model\Adminhtml\Source\Environment;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ENVIRONMENT = 'environment';
    const KEY_ACTIVE = 'active';
    const KEY_LIVE_PUBLISHABLE_KEY = 'live_publishable_key';
    const KEY_LIVE_SECRET_KEY = 'live_secret_key';
    const KEY_TEST_PUBLISHABLE_KEY = 'test_publishable_key';
    const KEY_TEST_SECRET_KEY = 'test_secret_key';
    const KEY_CURRENCY = 'currency';
    const KEY_CC_TYPES = 'cctypes';
    const KEY_CC_TYPES_STRIPE_MAPPER = 'cctypes_stripe_mapper';
    const KEY_USE_CCV = 'useccv';
    const KEY_ALLOW_SPECIFIC = 'allowspecific';
    const KEY_SPECIFIC_COUNTRY = 'specificcountry';

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCardTypes($storeId = null)
    {
        $ccTypes = $this->getValue(self::KEY_CC_TYPES, $storeId);

        return !empty($ccTypes) ? explode(',', $ccTypes) : [];
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getCcTypesMapper($storeId = null)
    {
        $result = json_decode(
            $this->getValue(self::KEY_CC_TYPES_STRIPE_MAPPER, $storeId),
            true
        );

        return is_array($result) ? $result : [];
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCurrency($storeId = null)
    {
        return $this->getValue(self::KEY_CURRENCY, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isCcvEnabled($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_USE_CCV, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEnvironment($storeId = null)
    {
        return $this->getValue(Config::KEY_ENVIRONMENT, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getPublishableKey($storeId = null)
    {
        if ($this->isTestMode()) {
            return $this->getValue(self::KEY_TEST_PUBLISHABLE_KEY, $storeId);
        }
        return $this->getValue(self::KEY_LIVE_PUBLISHABLE_KEY, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getSecretKey($storeId = null)
    {
        if ($this->isTestMode()) {
            return $this->getValue(self::KEY_TEST_SECRET_KEY, $storeId);
        }
        return $this->getValue(self::KEY_LIVE_SECRET_KEY, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isTestMode($storeId = null)
    {
        return (bool) $this->getEnvironment($storeId) == Environment::ENVIRONMENT_TEST;
    }
}
