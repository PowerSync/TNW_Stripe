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
 * @copyright Copyright (c) 2017-2022
 * @license   Open Software License (OSL 3.0)
 */

namespace TNW\Stripe\ViewModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use TNW\Stripe\Model\Customer\StoredCardsManagement;
use TNW\Stripe\Model\Ui\ConfigProvider;

class StoredCards implements ArgumentInterface
{
    /**
     * @var StoredCardsManagement
     */
    private $storedCardsManagement;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var PaymentMethodListInterface
     */
    private $paymentMethodList;

    /**
     * @var Data
     */
    private $paymentHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param StoredCardsManagement $storedCardsManagement
     * @param Session $customerSession
     * @param PaymentMethodListInterface $paymentMethodList
     * @param Data $paymentHelper
     * @param StoreManagerInterface $storeManager
     * @param PaymentFactory $paymentFactory
     * @param QuoteFactory $quoteFactory
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        StoredCardsManagement $storedCardsManagement,
        Session $customerSession,
        PaymentMethodListInterface $paymentMethodList,
        Data $paymentHelper,
        StoreManagerInterface $storeManager,
        PaymentFactory $paymentFactory,
        QuoteFactory $quoteFactory,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->storedCardsManagement = $storedCardsManagement;
        $this->customerSession = $customerSession;
        $this->paymentMethodList = $paymentMethodList;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->paymentFactory = $paymentFactory;
        $this->quoteFactory = $quoteFactory;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return bool
     */
    public function isStoredCardsExist()
    {
        return !empty($this->getCustomerTokens());
    }

    /**
     * @return PaymentTokenInterface[]
     */
    public function getCustomerTokens()
    {
        $tokens = [];
        try {
            if ($customerId = $this->getCustomer()->getId()) {
                $tokens = $this->storedCardsManagement->getByCustomerId($customerId);
            }
        } catch (NoSuchEntityException|LocalizedException $e) {
        }
        return $tokens;
    }

    /**
     * @return null|MethodInterface
     */
    public function getPaymentMethodInstance()
    {
        $storeId = (int) $this->getStoreId();
        foreach ($this->paymentMethodList->getActiveList($storeId) as $method) {
            if ($method->getCode() === ConfigProvider::CODE) {
                try {
                    $methodInstance = $this->paymentHelper->getMethodInstance($method->getCode());
                    if ($methodInstance->isAvailable()) {
                        $methodInstance->setInfoInstance(
                            $this->paymentFactory->create()->setQuote($this->quoteFactory->create())
                        );
                    } else {
                        unset($methodInstance);
                    }
                } catch (LocalizedException $e) {
                    continue;
                }
            }
        }
        return $methodInstance ?? null;
    }

    /**
     * @return int|null
     */
    private function getStoreId()
    {
        try {
            $storeId = $this->getCustomer()->getStoreId();
            if (!$storeId) {
                $website = $this->storeManager->getWebsite($this->getCustomer()->getWebsiteId());
                $store = $website->getDefaultStore();
                if ($store instanceof Store) {
                    $storeId = $store->getId();
                }
            }
            return $storeId;
        } catch (NoSuchEntityException | LocalizedException $exception) {
            return null;
        }
    }

    /**
     * @return CustomerInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomer()
    {
        if ($this->customerSession->getCustomerId()) {
            return $this->customerSession->getCustomerData();
        }
        if ($customerId = $this->request->getParam('id')) {
            return $this->customerRepository->getById($customerId);
        }
        return null;
    }
}
