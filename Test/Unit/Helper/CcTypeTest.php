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
namespace TNW\Stripe\Test\Unit\Helper;

use TNW\Stripe\Model\Adminhtml\Source\Cctype as CcTypeSource;
use TNW\Stripe\Helper\CcType;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CcTypeTest extends \PHPUnit\Framework\TestCase
{
  /** @var ObjectManager */
  private $objectManager;

  /** @var CcType */
  private $helper;

  /** @var CcTypeSource|\PHPUnit_Framework_MockObject_MockObject */
  private $ccTypeSource;
  
  protected function setUp() {
    $this->objectManager = new ObjectManager($this);

    $this->ccTypeSource = $this->getMockBuilder(CcTypeSource::class)
      ->disableOriginalConstructor()
      ->setMethods(['toOptionArray'])
      ->getMock();

    $this->helper = $this->objectManager->getObject(
      CcType::class,
      ['ccTypeSource' => $this->ccTypeSource]
    );
  }

  public function testGetCcTypes() {
    $this->ccTypeSource->expects($this->once())
      ->method('toOptionArray')
      ->willReturn([
        'label' => 'Visa', 'value' => 'VI'
      ]);
    $this->helper->getCcTypes();
    $this->ccTypeSource->expects($this->never())
      ->method('toOptionArray');
    $this->helper->getCcTypes();
  }
}
