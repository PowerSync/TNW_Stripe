<?php

namespace TNW\Stripe\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Class CountryCreditCard
 */
class CountryCreditCard extends AbstractFieldArray
{
    /**
     * @var Countries
     */
    private $countryRenderer = null;

    /**
     * @var CcTypes
     */
    private $ccTypesRenderer = null;

    /**
     * Returns renderer for country element
     *
     * @return Countries
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCountryRenderer()
    {
        if (!$this->countryRenderer) {
            $this->countryRenderer = $this->getLayout()->createBlock(
                Countries::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->countryRenderer;
    }

    /**
     * Returns renderer for country element
     *
     * @return CcTypes
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCcTypesRenderer()
    {
        if (!$this->ccTypesRenderer) {
            $this->ccTypesRenderer = $this->getLayout()->createBlock(
                CcTypes::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->ccTypesRenderer;
    }

    /**
     * Prepare to render
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('country_id', [
            'label'     => __('Country'),
            'renderer'  => $this->getCountryRenderer(),
        ]);

        $this->addColumn('cc_types', [
            'label' => __('Allowed Credit Card Types'),
            'renderer'  => $this->getCcTypesRenderer(),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Rule');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $country = $row->getData('country_id');
        if ($country) {
            $options['option_' . $this->getCountryRenderer()->calcOptionHash($country)]
                = 'selected="selected"';

            $ccTypes = $row->getData('cc_types');
            foreach ($ccTypes as $cardType) {
                $options['option_' . $this->getCcTypesRenderer()->calcOptionHash($cardType)]
                    = 'selected="selected"';
            }
        }

        $row->setData('option_extra_attrs', $options);
    }
}
