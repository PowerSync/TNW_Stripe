<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Helper;

use TNW\Stripe\Helper\Country;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class CountryTest
 */
class CountryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var Country
     */
    private $helper;

    protected function setUp()
    {
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadData', 'toOptionArray'])
            ->getMock();

        $this->collection->expects(static::once())
            ->method('loadData')
            ->willReturnSelf();

        /** @var CollectionFactory|MockObject $collectionFactory */
        $collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $collectionFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->collection);

        $this->helper = new Country(
            $collectionFactory
        );
    }

    /**
     * @covers Country::getCountries
     */
    public function testGetCountries()
    {
        $expected = [
            ['value' => 'US', 'label' => 'United States'],
            ['value' => 'UK', 'label' => 'United Kingdom'],
        ];

        $this->collection->expects(static::once())
            ->method('toOptionArray')
            ->with(false)
            ->willReturn($expected);

        static::assertEquals($expected, $this->helper->getCountries());

        $this->collection->expects(static::never())
            ->method('toOptionArray');

        static::assertEquals($expected, $this->helper->getCountries());
    }
}
