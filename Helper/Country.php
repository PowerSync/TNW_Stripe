<?php

namespace TNW\Stripe\Helper;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;

/**
 * Class Country
 */
class Country
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var array
     */
    private $countries;

    /**
     * @param CollectionFactory $factory
     */
    public function __construct(CollectionFactory $factory)
    {
        $this->collectionFactory = $factory;
    }

    /**
     * Returns countries array
     *
     * @return array
     */
    public function getCountries()
    {
        if (!$this->countries) {
            $this->countries = $this->countryCollection()
                ->loadData()
                ->toOptionArray(false);
        }

        return $this->countries;
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    private function countryCollection()
    {
        return $this->collectionFactory->create();
    }
}
