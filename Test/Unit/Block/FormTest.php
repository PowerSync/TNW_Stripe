<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Block;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use TNW\Stripe\Block\Form;
use TNW\Stripe\Gateway\Config\Config as GatewayConfig;
use TNW\Stripe\Model\Ui\ConfigProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class FormTest
 */
class FormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config|MockObject
     */
    private $gatewayConfig;

    /**
     * @var Data|MockObject
     */
    private $paymentDataHelper;

    /**
     * @var StoreManager|MockObject
     */
    private $storeManager;

    /**
     * @var StoreManager|MockObject
     */
    private $store;

    /**
     * @var Form
     */
    private $block;

    protected function setUp()
    {
        $this->gatewayConfig = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCcvEnabled'])
            ->getMock();

        $this->paymentDataHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodInstance'])
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->storeManager
            ->method('getStore')
            ->willReturn($this->store);

        $managerHelper = new ObjectManager($this);
        $this->block = $managerHelper->getObject(Form::class, [
            'paymentConfig' => $managerHelper->getObject(Config::class),
            'gatewayConfig' => $this->gatewayConfig,
            'helper' => $this->paymentDataHelper,
        ]);

        $managerHelper->setBackwardCompatibleProperty($this->block, '_storeManager', $this->storeManager);
    }

    public function testUseCcv()
    {
        $this->gatewayConfig
            ->method('isCcvEnabled')
            ->willReturn(true);

        self::assertTrue($this->block->useCcv());
    }

    public function testIsVaultEnabled()
    {
        $storeId = 1;

        $this->store
            ->method('getId')
            ->willReturn($storeId);

        $vaultPayment = $this->getMockForAbstractClass(VaultPaymentInterface::class);
        $this->paymentDataHelper->method('getMethodInstance')
            ->with(ConfigProvider::CC_VAULT_CODE)
            ->willReturn($vaultPayment);

        $vaultPayment->method('isActive')
            ->with($storeId)
            ->willReturn(true);

        self::assertTrue($this->block->isVaultEnabled());
    }
}
