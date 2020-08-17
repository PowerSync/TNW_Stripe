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

/**
 * Class ResponseValidator
 * @package TNW\Stripe\Gateway\Validator
 */
class ResponseValidator extends GeneralResponseValidator
{
    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return [
            function ($response) {
                if (!\in_array(
                    $response['status'],
                    ['succeeded', 'paid', 'pending', 'requires_confirmation', 'requires_capture', '']
                )) {
                    return [false, [__('Wrong transaction status')]];
                }
                return [true, []];
            }
        ];
    }
}
