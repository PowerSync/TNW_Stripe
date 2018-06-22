<?php
/**
 *
 */
namespace TNW\Stripe\Test\Unit\Gateway\Helper;

use InvalidArgumentException;
use TNW\Stripe\Gateway\Helper\SubjectReader;
use Stripe\StripeObject;

/**
 * Test SubjectReader
 */
class SubjectReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    protected function setUp()
    {
        $this->subjectReader = new SubjectReader();
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readCustomerId
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The "customerId" field does not exists
     */
    public function testReadCustomerIdWithException()
    {
        $this->subjectReader->readCustomerId([]);
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readCustomerId
     */
    public function testReadCustomerId()
    {
        $customerId = 1;
        static::assertEquals($customerId, $this->subjectReader->readCustomerId(['customer_id' => $customerId]));
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readTransaction
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Response object does not exist
     */
    public function testReadTransactionWithException()
    {
        $this->subjectReader->readTransaction([]);
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readTransaction
     */
    public function testReadTransaction()
    {
        $object = new StripeObject();
        static::assertEquals([], $this->subjectReader->readTransaction(['object' => $object]));
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readResponseObject
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Response does not exist
     */
    public function testReadResponseObjectWithException()
    {
        $this->subjectReader->readResponseObject([]);
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readResponseObject
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Response object does not exist
     */
    public function testReadResponseObjectWithExceptionObject()
    {
        $this->subjectReader->readResponseObject(['response' => []]);
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readResponseObject
     */
    public function testReadResponseObject()
    {
        $object = new StripeObject();
        static::assertEquals([], $this->subjectReader->readResponseObject(['response' => ['object' => $object]]));
    }

    /**
     * @covers \TNW\Stripe\Gateway\Helper\SubjectReader::readResponseObject
     */
    public function testReadResponseObjectError()
    {
        $object = new \Stripe\Error\Card('Test Message', null, null, null, null, null);

        static::assertEquals(
            [
                'error' => true,
                'message' => __('Test Message')
            ],
            $this->subjectReader->readResponseObject(['response' => ['object' => $object]])
        );
    }
}
