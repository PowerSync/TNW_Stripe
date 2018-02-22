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
namespace TNW\Stripe\Gateway\Http\Client;

use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Psr\Log\LoggerInterface;

abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Logger
     */
    private $customLogger;

    /**
     * @var StripeAdapterFactory
     */
    protected $adapterFactory;

    /**
     * AbstractTransaction constructor.
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param StripeAdapterFactory $adapterFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Logger $customLogger,
        StripeAdapterFactory $adapterFactory
    ) {
        $this->logger = $logger;
        $this->customLogger = $customLogger;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function placeRequest(
        TransferInterface $transferObject
    ) {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong.');
            $this->logger->critical($message);
            throw new ClientException($message);
        } finally {
            $log['response'] = (array) $response['object'];
            $this->customLogger->debug($log);
        }

        return $response;
    }

    /**
     * Process http request
     * @param array $data
     * @return mixed
     */
    abstract protected function process(array $data);
}
