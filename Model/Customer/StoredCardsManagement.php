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
namespace TNW\Stripe\Model\Customer;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use TNW\Stripe\Api\StoredCardsManagementInterface;
use TNW\Stripe\Model\Ui\ConfigProvider;
use TNW\Stripe\Gateway\Http\TransferFactory;
use TNW\Stripe\Gateway\Request\StoredCardDataBuilder;
use TNW\Stripe\Gateway\Http\Client\TransactionSale;
use TNW\Stripe\Gateway\Http\Client\TransactionVoid;
use TNW\Stripe\Gateway\Validator\ResponseValidator\Authorize;
use TNW\Stripe\Gateway\Validator\GeneralResponseValidator;

class StoredCardsManagement implements StoredCardsManagementInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    private $transferFactory;

    private $dataBuilder;

    private $client;

    private $cancelClient;

    private $validator;

    private $tokenExtractor;

    private $voidValidator;

    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        TransferFactory $transferFactory,
        StoredCardDataBuilder $dataBuilder,
        TransactionSale $client,
        TransactionVoid $cancelClient,
        Authorize $validator,
        TokenExtractor $tokenExtractor,
        GeneralResponseValidator $voidValidator
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->transferFactory = $transferFactory;
        $this->dataBuilder = $dataBuilder;
        $this->client = $client;
        $this->cancelClient = $cancelClient;
        $this->validator = $validator;
        $this->tokenExtractor = $tokenExtractor;
        $this->voidValidator = $voidValidator;
    }

    public function getByCustomerId(int $customerId)
    {
        $storedCards = [];
        foreach ($this->paymentTokenManagement->getVisibleAvailableTokens($customerId) as $token) {
            if ($token->getPaymentMethodCode() === ConfigProvider::CODE) {
                $storedCards[] = $token;
            }
        }
        return $storedCards;
    }

    /**
     * @inheritDoc
     */
    public function save(string $token, CustomerInterface $customer, array $arguments = [])
    {
        try {
            $paymentData = [
                'customer' => $customer,
                'token' => $token
            ];
            $paymentTransactionData = $this->dataBuilder->build($paymentData);
            $transferO = $this->transferFactory->create($paymentTransactionData);
            $response = $this->client->placeRequest($transferO);

            $result = $this->validator->validate(
                array_merge($paymentData, ['response' => $response])
            );
            if (!$result->isValid()) {
                return false;
            }
            $paymentTokenData = $this->tokenExtractor
                ->getPaymentTokenWithTransactionId($response, $customer, array_merge($arguments, $paymentData));

            $cancelRequest = [
                'transaction_id' => $paymentTokenData['transaction_id'],
            ];
            if (isset($paymentTransactionData['store_id'])) {
                $cancelRequest['store_id'] = $paymentTransactionData['store_id'];
            }
            $cancelRequest['paymentTransactionData'] = $paymentTransactionData;
            $transferCancelObject = $this->transferFactory->create($cancelRequest);
            $responseCancel = $this->cancelClient->placeRequest($transferCancelObject);

            $voidResult = $this->voidValidator->validate(
                array_merge($paymentData, ['response' => $responseCancel])
            );
            if (!$voidResult->isValid()) {
                //TODO: add logging
            }
            $this->paymentTokenRepository->save($paymentTokenData['payment_token']);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $hash, int $customerId)
    {
        $paymentToken = $this->paymentTokenManagement->getByPublicHash($hash, $customerId);
        if (!$paymentToken) {
            throw new NoSuchEntityException(__('Payment Token does not exist.'));
        }
        return $this->paymentTokenRepository->delete($paymentToken);
    }
}
