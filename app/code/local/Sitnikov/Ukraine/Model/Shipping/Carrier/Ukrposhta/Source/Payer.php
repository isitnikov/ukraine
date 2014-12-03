<?php
class Sitnikov_Ukraine_Model_Shipping_Carrier_Ukrposhta_Source_Payer
{
    public function toOptionArray()
    {
        /** @var Sitnikov_Ukraine_Model_Shipping_Carrier_Ukrposhta $ukrposhta */
        $ukrposhta = Mage::getSingleton('ukraine/shipping_carrier_ukrposhta');
        $arr = array();
        foreach ($ukrposhta->getCode('payer') as $k => $v) {
            $arr[] = array('value' => $k, 'label' => $v);
        }
        return $arr;
    }
}
