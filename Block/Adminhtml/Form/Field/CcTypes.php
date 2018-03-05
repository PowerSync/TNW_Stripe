<?php

namespace TNW\Stripe\Block\Adminhtml\Form\Field;

use TNW\Stripe\Helper\CcType;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class CcTypes
 */
class CcTypes extends Select
{
    /**
     * @var CcType
     */
    private $ccTypeHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CcType $ccTypeHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CcType $ccTypeHelper,
        array $data = []
    ) {
        $this->ccTypeHelper = $ccTypeHelper;

        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setOptions($this->ccTypeHelper->getCcTypes())
            ->setClass('cc-type-select')
            ->setData('extra_params', 'multiple="multiple"');
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setData('name', $value . '[]');
    }
}
