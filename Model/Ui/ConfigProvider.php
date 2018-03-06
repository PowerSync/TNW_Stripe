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
use Magento\Framework\Session\SessionManagerInterface;
use TNW\Stripe\Gateway\Config\Config;

/**
 * Config Provider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'tnw_stripe';
    const CC_VAULT_CODE = 'tnw_stripe_vault';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * Constructor.
     * @param Config $config
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Config $config,
        SessionManagerInterface $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $storeId = $this->session->getStoreId();
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive($storeId),
                    'publishableKey' => $this->config->getPublishableKey(),
                    'vaultCode' => self::CC_VAULT_CODE,
                    'ccTypesMapper' => $this->config->getCctypesMapper(),
                    'sdkUrl' => $this->config->getSdkUrl(),
                    'countrySpecificCardTypes' => $this->config->getCountrySpecificCardTypeConfig($storeId),
                    'availableCardTypes' => $this->config->getAvailableCardTypes($storeId),
                    'useCvv' => $this->config->isCvvEnabled($storeId),
                ]
            ]
        ];
    }
}
