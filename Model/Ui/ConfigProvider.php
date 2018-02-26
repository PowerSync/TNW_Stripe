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
namespace TNW\Stripe\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use TNW\Stripe\Gateway\Config\Config;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'tnw_stripe';
    const CC_VAULT_CODE = 'tnw_stripe_vault';

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                  'publishableKey' => $this->getPublishableKey(),
                  'vaultCode' => self::CC_VAULT_CODE,
                ]
            ]
        ];
    }

    public function getPublishableKey()
    {
        return $this->config->getPublishableKey();
    }
}
