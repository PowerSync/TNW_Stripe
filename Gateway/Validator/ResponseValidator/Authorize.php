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

use TNW\Stripe\Gateway\Validator\ResponseValidator;

/**
 * Class Authorize
 * @package TNW\Stripe\Gateway\Validator\ResponseValidator
 */
class Authorize extends ResponseValidator
{
    /**
     * @return array
     */
    protected function getResponseValidators()
    {
        return array_merge(
            parent::getResponseValidators(),
            [
                function ($response) {
                    if (!isset($response['charges']['data'][0]['outcome'])) {
                        return [true, []];
                    }
                    if ($response['charges']['data'][0]['outcome']['network_status'] !== 'approved_by_network') {
                        return [false, [__('Transaction has been declined')]];
                    }
                    return [true, []];
                }
            ]
        );
    }
}
