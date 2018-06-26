<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Request;

use TNW\Stripe\Gateway\Request\SettlementDataBuilder;

class SettlementDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SettlementDataBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->builder = new SettlementDataBuilder();
    }

    public function testBuild()
    {
        $expected = [
            SettlementDataBuilder::CAPTURE => true
        ];

        $buildSubject = [];

        self::assertEquals($expected, $this->builder->build($buildSubject));
    }
}
