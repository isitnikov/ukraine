<?php

class Sitnikov_Ukraine_Block_Adminhtml_System_Config_Ukrposhta_Rate extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @var string
     */
    protected $_wizardTemplate = 'sitnikov/ukraine/system/config/ukrposhta/rate.phtml';

    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate($this->_wizardTemplate);
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $elementHtmlId = $element->getHtmlId();
        $this->addData(
            $this->_getButtonData($elementHtmlId, $originalData)
        );
        return $this->_toHtml();
    }

    /**
     * Prepare button data
     *
     * @param string $elementHtmlId
     * @param array $originalData
     * @return array
     */
    protected function _getButtonData($elementHtmlId, $originalData)
    {
        return array(
            'button_label' => Mage::helper('ukraine')->__($originalData['button_label']),
            'button_url'   => $originalData['button_url'],
            'html_id' => $elementHtmlId,
        );
    }
}
