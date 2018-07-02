<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Validator;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Validator\ResponseValidator;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Test ResponseValidatorTest
 */
class ResponseValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResponseValidator
     */
    private $responseValidator;

    /**
     * @var ResultInterfaceFactory|MockObject
     */
    private $resultInterfaceFactory;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReader;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resultInterfaceFactory = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['readResponseObject'])
            ->getMock();

        $this->responseValidator = new ResponseValidator(
            $this->resultInterfaceFactory,
            $this->subjectReader
        );
    }

    /**
     * Run test for validate method
     *
     * @param array $validationSubject
     * @param bool $isValid
     * @param Phrase[] $messages
     * @return void
     *
     * @dataProvider dataProviderTestValidate
     */
    public function testValidate(array $validationSubject, $isValid, $messages)
    {
        $this->resultInterfaceFactory->method('create')
            ->with([
                'isValid' => (bool)$isValid,
                'failsDescription' => $messages
            ]);

        $this->subjectReader->method('readResponseObject')
            ->with(['response' => ['object' => $validationSubject]])
            ->willReturn($validationSubject);

        $this->responseValidator->validate(['response' => ['object' => $validationSubject]]);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate()
    {
        return [
            [
                [
                    'status' => 'succeeded'
                ],
                true,
                []
            ],
            [
                [
                    'error' => true,
                    'message' => 'Test error message.'
                ],
                false,
                [
                    __('Test error message.')
                ]
            ],
            [
                [
                    'status' => 'failed'
                ],
                'isValid' => false,
                [
                    __('Stripe error response.')
                ]
            ],
            [
                [
                    'status' => 'pending'
                ],
                'isValid' => false,
                [
                    __('Stripe error response.')
                ]
            ],
        ];
    }
}
