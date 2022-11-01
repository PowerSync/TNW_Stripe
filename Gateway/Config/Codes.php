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
namespace TNW\Stripe\Gateway\Config;

/**
 * Class Codes - used to define exception codes for stripe decline codes
 */
class Codes
{
    const AUTHENTIFICATION_REQUIRED = '10001';
    const DEFAULT_CODE = '1000';

    /**
     * @param $declineCode
     * @return mixed|string
     */
    public static function getExceptionCodeByDeclineCode($declineCode)
    {
        $stripeCodesToLocal = [
            "authentication_required" => self::AUTHENTIFICATION_REQUIRED
        ];

        if (isset($stripeCodesToLocal[$declineCode])) {
            return $stripeCodesToLocal[$declineCode];
        }
        return self::DEFAULT_CODE;
    }
}
