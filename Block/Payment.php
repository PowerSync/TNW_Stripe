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
namespace TNW\Stripe\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Stripe\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Payment
 */
class Payment extends Template
{
    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ConfigProvider $config
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $config,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->serializer = $serializer;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getPaymentConfig()
    {
        $payment = $this->config->getConfig()['payment'];
        $config = $payment[$this->getCode()];
        $config['code'] = $this->getCode();
        if ($store = $this->_storeManager->getStore()) {
            $config['currencyCode'] = $store->getCurrentCurrencyCode();
        }
        return $this->serializer->serialize($config);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return ConfigProvider::CODE;
    }
}
