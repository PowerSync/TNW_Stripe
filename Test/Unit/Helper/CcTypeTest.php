<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Helper;

use TNW\Stripe\Helper\CcType;
use TNW\Stripe\Model\Adminhtml\Source\CcType as CcTypeSource;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class CcTypeTest
 */
class CcTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CcTypeSource|MockObject
     */
    private $ccTypeSource;

    /**
     * @var CcType
     */
    private $helper;

    protected function setUp()
    {
        $this->ccTypeSource = $this->getMockBuilder(CcTypeSource::class)
            ->disableOriginalConstructor()
            ->setMethods(['toOptionArray'])
            ->getMock();

        $this->helper = new CcType(
            $this->ccTypeSource
        );
    }

    /**
     * @covers CcType::getCcTypes
     */
    public function testGetCcTypes()
    {
        $expected = [
            'label' => 'VISA',
            'value' => 'VI'
        ];

        $this->ccTypeSource->expects(static::once())
            ->method('toOptionArray')
            ->willReturn($expected);

        static::assertEquals($expected, $this->helper->getCcTypes());

        $this->ccTypeSource->expects(static::never())
            ->method('toOptionArray');

        static::assertEquals($expected, $this->helper->getCcTypes());
    }
}
