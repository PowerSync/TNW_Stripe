<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Model\Ui\Adminhtml;

use TNW\Stripe\Model\Ui\Adminhtml\TokenUiComponentProvider;
use TNW\Stripe\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class TokenUiComponentProviderTest
 */
class TokenUiComponentProviderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var TokenUiComponentInterfaceFactory|MockObject
     */
    private $componentFactory;

    /**
     * @var TokenUiComponentProvider
     */
    private $tokenUiComponentProvider;

    protected function setUp()
    {
        $this->componentFactory = $this->getMockBuilder(TokenUiComponentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->tokenUiComponentProvider = new TokenUiComponentProvider(
            $this->componentFactory
        );
    }

    /**
     * @covers TokenUiComponentProvider::getComponentForToken
     */
    public function testGetComponentForToken()
    {
        $expected = [
            'config' => [
                'code' => ConfigProvider::CC_VAULT_CODE,
                TokenUiComponentProviderInterface::COMPONENT_DETAILS => [
                    'type' => 'VI',
                    'maskedCC' => '1111',
                    'expirationDate' => '12/2015',
                ],
                TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => '37du7ir5ed',
                'template' => 'TNW_Stripe::form/vault.phtml'
            ],
            'name' => Template::class
        ];

        /** @var PaymentTokenInterface|MockObject $paymentToken */
        $paymentToken = $this->createMock(PaymentTokenInterface::class);
        $paymentToken->expects(static::once())
            ->method('getTokenDetails')
            ->willReturn('{"type":"VI","maskedCC":"1111","expirationDate":"12\/2015"}');
        $paymentToken->expects(static::once())
            ->method('getPublicHash')
            ->willReturn('37du7ir5ed');

        $tokenComponent = $this->createMock(TokenUiComponentInterface::class);

        $this->componentFactory->expects(static::once())
            ->method('create')
            ->with($expected)
            ->willReturn($tokenComponent);

        $component = $this->tokenUiComponentProvider->getComponentForToken($paymentToken);
        static::assertEquals($tokenComponent, $component);
    }
}
