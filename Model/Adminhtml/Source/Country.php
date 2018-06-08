<?php

namespace TNW\Stripe\Model\Adminhtml\Source;

use TNW\Stripe\Helper\Country as CountryHelper;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Country
 */
class Country implements ArrayInterface
{
    /**
     * Country Helper
     *
     * @var CountryHelper
     */
    private $country;

    /**
     * @param CountryHelper $country
     */
    public function __construct(CountryHelper $country)
    {
        $this->country = $country;
    }

    /**
     * @param bool $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        $options = $this->country->getCountries();
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }
}
