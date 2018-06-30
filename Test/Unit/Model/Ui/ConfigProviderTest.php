<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Model\Ui;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Model\Ui\ConfigProvider;
use Magento\Customer\Model\Session;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * ConfigProvider Test
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var Session|MockObject
     */
    private $session;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Repository|MockObject
     */
    private $assetRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetRepository = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = new ConfigProvider(
            $this->config,
            $this->session,
            $this->urlBuilder,
            $this->assetRepository
        );
    }

    /**
     * Run test getConfig method
     *
     * @covers ConfigProvider::getConfig()
     */
    public function testGetConfig()
    {
        $this->session->method('getStoreId')
            ->willReturn(5);

        $this->config->method('isActive')
            ->with(5)
            ->willReturn(true);

        $this->config->method('getPublishableKey')
            ->with(5)
            ->willReturn('publishable_key');

        $this->config->method('getCctypesMapper')
            ->with(5)
            ->willReturn(['test']);

        $this->config->method('getSdkUrl')
            ->with(5)
            ->willReturn('sdk_url');

        $this->urlBuilder->method('getUrl')
            ->with('tnw_stripe/window/close')
            ->willReturn('close_url');

        $this->config->method('getCountrySpecificCardTypeConfig')
            ->with(5)
            ->willReturn(['test']);

        $this->config->method('getAvailableCardTypes')
            ->with(5)
            ->willReturn(['test']);

        $this->config->method('isCvvEnabled')
            ->with(5)
            ->willReturn(true);

        $this->assetRepository->method('getUrl')
            ->with('TNW_Stripe::images/loader.gif')
            ->willReturn('img_url');

        $expected = [
            'payment' => [
                ConfigProvider::CODE => [
                    'isActive' => true,
                    'publishableKey' => 'publishable_key',
                    'vaultCode' => ConfigProvider::CC_VAULT_CODE,
                    'ccTypesMapper' => ['test'],
                    'sdkUrl' => 'sdk_url',
                    'returnUrl' => 'close_url',
                    'countrySpecificCardTypes' => ['test'],
                    'availableCardTypes' => ['test'],
                    'useCvv' => true,
                    'imgLoading' => 'img_url',
                ],
            ]
        ];

        self::assertEquals($expected, $this->configProvider->getConfig());
    }
}
