<?php
/**
 *
 */
namespace TNW\Stripe\Test\Unit\Gateway\Config;

use TNW\Stripe\Gateway\Config\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Test Config
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    const METHOD_CODE = 'tnw_stripe';

    /**
     * @var Config
     */
    private $model;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->model = new Config(
            $this->scopeConfigMock,
            self::METHOD_CODE
        );
    }

    /**
     * @param string $encodedValue
     * @param array $expected
     * @dataProvider getCountrySpecificCardTypeConfigDataProvider
     * @covers \TNW\Stripe\Gateway\Config\Config::getCountrySpecificCardTypeConfig
     */
    public function testGetCountrySpecificCardTypeConfig($encodedValue, array $expected)
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_COUNTRY_CREDIT_CARD), ScopeInterface::SCOPE_STORE, null)
            ->willReturn($encodedValue);

        static::assertEquals(
            $expected,
            $this->model->getCountrySpecificCardTypeConfig()
        );
    }

    /**
     * @return array
     */
    public function getCountrySpecificCardTypeConfigDataProvider()
    {
        return [
            'valid data' => [
                'a:2:{s:2:"GB";a:2:{i:0;s:2:"VI";i:1;s:2:"AE";}s:2:"US";a:2:{i:0;s:2:"DI";i:1;s:3:"JCB";}}',
                ['GB' => ['VI', 'AE'], 'US' => ['DI', 'JCB']]
            ],
            'non-array value' => [
                's:2:"""";',
                []
            ]
        ];
    }

    /**
     * @param string $value
     * @param array $expected
     * @dataProvider getAvailableCardTypesDataProvider
     * @covers \TNW\Stripe\Gateway\Config\Config::getAvailableCardTypes
     */
    public function testGetAvailableCardTypes($value, $expected)
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_CC_TYPES), ScopeInterface::SCOPE_STORE, null)
            ->willReturn($value);

        static::assertEquals(
            $expected,
            $this->model->getAvailableCardTypes()
        );
    }

    /**
     * @return array
     */
    public function getAvailableCardTypesDataProvider()
    {
        return [
            [
                'AE,VI,MC,DI,JCB',
                ['AE', 'VI', 'MC', 'DI', 'JCB']
            ],
            [
                '',
                []
            ]
        ];
    }

    /**
     * @param string $value
     * @param array $expected
     * @dataProvider getCcTypesMapperDataProvider
     */
    public function testGetCcTypesMapper($value, $expected)
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_CC_TYPES_STRIPE_MAPPER), ScopeInterface::SCOPE_STORE, null)
            ->willReturn($value);

        static::assertEquals(
            $expected,
            $this->model->getCctypesMapper()
        );
    }

    /**
     * @return array
     */
    public function getCcTypesMapperDataProvider()
    {
        return [
            [
                '{"visa":"VI","american-express":"AE"}',
                ['visa' => 'VI', 'american-express' => 'AE']
            ],
            [
                '{invalid json}',
                []
            ],
            [
                '',
                []
            ]
        ];
    }

    /**
     * @covers \TNW\Stripe\Gateway\Config\Config::getCountryAvailableCardTypes
     * @dataProvider getCountrySpecificCardTypeConfigDataProvider
     * @param string $encodedData
     * @param string|array $data
     * @param array $countryData
     */
    public function testCountryAvailableCardTypes($encodedData, $data, array $countryData)
    {
        $this->scopeConfigMock->expects(static::any())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_COUNTRY_CREDIT_CARD), ScopeInterface::SCOPE_STORE, null)
            ->willReturn($encodedData);

        foreach ($countryData as $countryId => $types) {
            $result = $this->model->getCountryAvailableCardTypes($countryId);
            static::assertEquals($types, $result);
        }

        if (empty($countryData)) {
            static::assertEquals($data, "");
        }
    }

    /**
     * @covers \TNW\Stripe\Gateway\Config\Config::isCvvEnabled
     */
    public function testUseCvv()
    {
        $this->scopeConfigMock->expects(static::any())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_USE_CVV), ScopeInterface::SCOPE_STORE, null)
            ->willReturn(1);

        static::assertEquals(true, $this->model->isCvvEnabled());
    }

    /**
     * Return config path
     *
     * @param string $field
     * @return string
     */
    private function getPath($field)
    {
        return sprintf(Config::DEFAULT_PATH_PATTERN, self::METHOD_CODE, $field);
    }
}
