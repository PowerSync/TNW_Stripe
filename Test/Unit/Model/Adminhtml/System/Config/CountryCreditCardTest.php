<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Model\Adminhtml\System\Config;

use TNW\Stripe\Model\Adminhtml\System\Config\CountryCreditCard;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class CountryCreditCardTest
 *
 */
class CountryCreditCardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AbstractResource|MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\Math\Random|MockObject
     */
    protected $mathRandomMock;

    /**
     * @var CountryCreditCard
     */
    protected $model;

    protected function setUp()
    {
        $this->resourceMock = $this->getMockForAbstractClass(AbstractResource::class);
        $this->mathRandomMock = $this->getMockBuilder(Random::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(
            CountryCreditCard::class,
            [
                'mathRandom' => $this->mathRandomMock,
                'resource' => $this->resourceMock,
            ]
        );
    }

    /**
     * @dataProvider beforeSaveDataProvider
     * @param array $value
     * @param string $encodedValue
     */
    public function testBeforeSave(array $value, $encodedValue)
    {
        $this->model->setValue($value);

        $this->model->beforeSave();
        $this->assertEquals($encodedValue, $this->model->getValue());
    }

    /**
     * Get data for testing credit card types
     * @return array
     */
    public function beforeSaveDataProvider()
    {
        return [
            'empty_value' => [
                'value' => [],
                'encoded' => 'a:0:{}'
            ],
            'not_array' => [
                'value' => ['US'],
                'encoded' => 'a:0:{}'
            ],
            'array_with_invalid_format' => [
                'value' => [
                    [
                        'country_id' => 'US',
                    ],
                ],
                'encoded' => 'a:0:{}'
            ],
            'array_with_two_countries' => [
                'value' => [
                    [
                        'country_id' => 'AF',
                        'cc_types' => ['AE', 'VI']
                    ],
                    [
                        'country_id' => 'US',
                        'cc_types' => ['AE', 'VI', 'MA']
                    ],
                    '__empty' => '',
                ],
                'encoded' => 'a:2:{s:2:"AF";a:2:{i:0;s:2:"AE";i:1;s:2:"VI";}s:2:"US";a:3:{i:0;s:2:"AE";i:1;s:2:"VI";i:2;s:2:"MA";}}'
            ],
            'array_with_two_same_countries' => [
                'value' => [
                    [
                        'country_id' => 'AF',
                        'cc_types' => ['AE', 'VI']
                    ],
                    [
                        'country_id' => 'US',
                        'cc_types' => ['AE', 'VI', 'MA']
                    ],
                    [
                        'country_id' => 'US',
                        'cc_types' => ['VI', 'OT']
                    ],
                    '__empty' => '',
                ],
                'encoded' => 'a:2:{s:2:"AF";a:2:{i:0;s:2:"AE";i:1;s:2:"VI";}s:2:"US";a:4:{i:0;s:2:"AE";i:1;s:2:"VI";i:2;s:2:"MA";i:3;s:2:"OT";}}'
            ],
        ];
    }

    /**
     * @param string $encodedValue
     * @param array $hashData
     * @param array|null $expected
     * @dataProvider afterLoadDataProvider
     */
    public function testAfterLoad(
        $encodedValue,
        array $hashData,
        $expected
    ) {
        $this->model->setValue($encodedValue);
        $index = 0;
        foreach ($hashData as $hash) {
            $this->mathRandomMock->expects($this->at($index))
                ->method('getUniqueHash')
                ->willReturn($hash);
            $index++;
        }

        $this->model->afterLoad();
        $this->assertEquals($expected, $this->model->getValue());
    }

    /**
     * Get data to test saved credit cards types
     *
     * @return array
     */
    public function afterLoadDataProvider()
    {
        return [
            'empty' => [
                'encoded' => 'a:0:{}',
                'randomHash' => [],
                'expected' => []
            ],
            'null' => [
                'encoded' => '',
                'randomHash' => [],
                'expected' => null
            ],
            'valid data' => [
                'encoded' => 'a:2:{s:2:"US";a:3:{i:0;s:2:"AE";i:1;s:2:"VI";i:2;s:2:"MA";}s:2:"AF";a:2:{i:0;s:2:"AE";i:1;s:2:"MA";}}',
                'randomHash' => ['hash_1', 'hash_2'],
                'expected' => [
                    'hash_1' => [
                        'country_id' => 'US',
                        'cc_types' => ['AE', 'VI', 'MA']
                    ],
                    'hash_2' => [
                        'country_id' => 'AF',
                        'cc_types' => ['AE', 'MA']
                    ]
                ]
            ]
        ];
    }
}
