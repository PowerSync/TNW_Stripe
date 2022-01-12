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
namespace TNW\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use TNW\Stripe\Gateway\Helper\SubjectReader;

/**
 * Class AddressDataBuilder
 * @package TNW\Stripe\Gateway\Request
 */
class AddressDataBuilder implements BuilderInterface
{
    const SHIPPING_ADDRESS = 'shipping';
    const STREET_ADDRESS = 'line1';
    const EXTENDED_ADDRESS = 'line2';
    const LOCALITY = 'city';
    const REGION = 'state';
    const POSTAL_CODE = 'postal_code';
    const COUNTRY_CODE = 'country';
    const NAME = 'name';
    const PHONE = 'phone';

    private $subjectReader;

    /**
     * AddressDataBuilder constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $result = [];

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result[self::SHIPPING_ADDRESS] = [
                'address' => [
                    self::STREET_ADDRESS => method_exists($shippingAddress, 'getStreetLine')
                        ? $shippingAddress->getStreetLine(1)
                        : $shippingAddress->getStreetLine1(),
                    self::EXTENDED_ADDRESS => method_exists($shippingAddress, 'getStreetLine')
                        ? $shippingAddress->getStreetLine(2)
                        : $shippingAddress->getStreetLine2(),
                    self::LOCALITY => $shippingAddress->getCity(),
                    self::REGION => $shippingAddress->getRegionCode(),
                    self::POSTAL_CODE => $shippingAddress->getPostcode(),
                    self::COUNTRY_CODE => $shippingAddress->getCountryId()
                ],
                self::NAME => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                self::PHONE => $shippingAddress->getTelephone()
            ];
        }

        return $result;
    }
}
