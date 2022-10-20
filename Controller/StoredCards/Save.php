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

namespace TNW\Stripe\Controller\StoredCards;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Save implements AccountInterface, HttpPostActionInterface
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        // TODO: Implement execute() method.
    }
}
