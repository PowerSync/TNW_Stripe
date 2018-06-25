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

class ResponseValidator extends GeneralResponseValidator
{
    protected function getResponseValidators()
    {
        return array_merge(
            parent::getResponseValidators(),
            [
                function ($response) {
                    if (isset($response['error'])) {
                        return [false, [$response['message']]];
                    }

                    if (in_array($response['status'], ['succeeded', 'pending', 'failed'])) {
                        return [false, [__('Wrong transaction status')]];
                    }

                    return [true, []];
                }
            ]
        );
    }
}
