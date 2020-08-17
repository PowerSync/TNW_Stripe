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
namespace TNW\Stripe\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Class GeneralResponseValidator
 * @package TNW\Stripe\Gateway\Validator
 */
class GeneralResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * GeneralResponseValidator constructor.
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $subject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $subject)
    {
        $response = $this->subjectReader->readResponseObject($subject);

        $isValid = true;
        $errorMessages = [];

        foreach ($this->getResponseValidators() as $validator) {
            $validationResult = $validator($response);

            if (!$validationResult[0]) {
                $isValid = $validationResult[0];
                $errorMessages = array_merge($errorMessages, $validationResult[1]);
                break;
            }
        }

        return $this->createResult($isValid, $errorMessages);
    }

    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return [
            function ($response) {
                if (isset($response['error'])) {
                    return [false, [__($response['message'])]];
                }

                if ($response['status'] !== 'succeeded') {
                    return [false, [__('Stripe error response.')]];
                }

                return [true, []];
            }
        ];
    }
}
