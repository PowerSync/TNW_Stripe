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
namespace TNW\Stripe\Gateway\Validator\ResponseValidator;

use TNW\Stripe\Gateway\Validator\GeneralResponseValidator;

/**
 * Class Customer
 * @package TNW\Stripe\Gateway\Validator\ResponseValidator
 */
class Customer extends GeneralResponseValidator
{
    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return [
            function ($response) {
                if (!isset($response['id'])) {
                    return [false, [__('Customer create error')]];
                }

                return [true, []];
            }
        ];
    }
}
