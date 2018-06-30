<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Controller\Payment;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use TNW\Stripe\Controller\Window\Close;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Close Test
 */
class CloseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Raw|MockObject
     */
    private $raw;

    /**
     * @var Close
     */
    private $action;

    protected function setUp()
    {
        $rawFactory = $this->createMock(RawFactory::class);

        $this->raw = $this->createMock(Raw::class);

        $rawFactory->method('create')
            ->willReturn($this->raw);

        $managerHelper = new ObjectManager($this);
        $this->action = $managerHelper->getObject(Close::class, [
            'rawFactory' => $rawFactory,
        ]);
    }

    public function testExecute()
    {
        $this->raw
            ->method('setContents')
            ->with('<script type="text/javascript">window.parent.require(\'TNW_Stripe/js/featherlight\').current().close()</script>')
            ->willReturnSelf();

        static::assertEquals($this->raw, $this->action->execute());
    }
}
