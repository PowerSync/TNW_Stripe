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

use TNW\Stripe\Gateway\Config\Config;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use TNW\Stripe\Helper\Payment\Formatter;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Stripe\Customer;

class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;
  
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const SOURCE = 'source';
    const DESCRIPTION = 'description';
    const CAPTURE = 'capture';

    /** @var Config  */
    private $config;

    /** @var SubjectReader  */
    private $subjectReader;

    /** @var Session  */
    private $customerSession;

    /** @var CustomerRepositoryInterface  */
    private $customerRepository;

  /**
   * PaymentDataBuilder constructor.
   * @param Config $config
   * @param SubjectReader $subjectReader
   * @param Session $customerSession
   * @param CustomerRepositoryInterface $customerRepository
   */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

  /**
   * @param array $subject
   * @return array
   * @throws \Magento\Framework\Validator\Exception
   */
    public function build(array $subject)
    {
        $paymentDO = $this->subjectReader->readPayment($subject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $result = [
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($subject)),
            self::DESCRIPTION => $order->getOrderIncrementId(),
            self::CURRENCY => $this->config->getCurrency(),
            self::SOURCE => $payment->getAdditionalInformation('cc_token'),
            self::CAPTURE => false
        ];

        return $result;
    }
}
