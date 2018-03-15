<?php
/**
 * See LICENSE.txt for license details.
 */
namespace TNW\Stripe\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Class Hint adds "Configuration Details" link to payment configuration.
 * `<comment>` node must be defined in `<group>` node and contain some link.
 */
class Hint extends Template implements RendererInterface
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$element->getData('comment')) {
            return '';
        }

        return sprintf(
            '<tr id="row_%s"><td colspan="1"><p class="note"><span><a href="%s" target="_blank">%s</a></span></p></td></tr>',
            $element->getHtmlId(),
            $element->getData('comment'),
            __('Configuration Details')
        );
    }
}
