<?php

class Sitnikov_Ukraine_Model_Shipping_Carrier_Ukrposhta
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'ukrposhta';

    const FLAG_TRUE  = 'true';
    const FLAG_FALSE = 'false';

    // mailKind
    const TYPE_WRAPPER          = 'Wrapper';
    const TYPE_WRAPPER_PRIORITY = 'WrapperPriority';
    const TYPE_PARCEL           = 'Parcel';
    const TYPE_LETTER           = 'Mail';
    const TYPE_LETTER_PRIORITY  = 'MailPriority';

    /**
     * Simple
     */
    const MAIL_CATEGORY_SIMPLE     = 'Simple';
    /**
     * Ordinary parcel
     */
    const MAIL_CATEGORY_ORDINARY   = 'Ordinary';
    /**
     * Recommended
     */
    const MAIL_CATEGORY_DECLARED   = 'Declared';
    /**
     * With shared cost
     */
    const MAIL_CATEGORY_REGISTERED = 'Registered';

    // direction
    const DIRECTION_UKRAINE           = 'Ukraine';
    const DIRECTION_FOREIGN_COUNTRIES = 'ForeignCountries';

    // country
    const COUNTRY_NOT_DEFINED = 'NotACountry';

    // country (if direction = ForeignCountires)

    // transferMethod
    const TRANSFER_METHOD_GROUND      = 'Ground';
    const TRANSFER_METHOD_AIR         = 'Air';

    // senderKind
    const SENDER_KIND_NATURAL_PERSON  = 'NaturalPerson';
    const SENDER_KIND_LEGAL_ENTITY    = 'LegalEntity';

    // courierDostList
    const COURIER_NOT_DEFINED         = 'Choose';
    const COURIER_FROM_DOOR           = 'Zabir';
    const COURIER_FROM_DOOR_TO_DOOR   = 'Zabir_Arrived';
    const COURIER_TO_DOOR             = 'Arrived';

    // declaredValue
    // declaredValueCoin
    // postPay
    // postPayCoins
    // mass
    // massGramme

    // marks

    // withHanding      - c уведомлением о вручении
    // isFragile        - хрупкая
    // isBulky          - громоздкая
    // handPersonally   - вручить лично
    // withMessenger    - с посыльным

    // packing  - упаковка
    // withForm - заполнение сопроводительного бланка
    // withAddress - заполнение адреса
    // withF103 - заполнение списка Ф.103

    /*
     *
     *
     * , <input type=​"checkbox" id=​"packing" name=​"packing" onclick=​"marksChange()​;​">​,
     * <input type=​"checkbox" id=​"withForm" name=​"withForm" onclick=​"marksChange()​;​" disabled>​,
     * <input type=​"checkbox" id=​"withAddress" name=​"withAddress" onclick=​"marksChange()​;​" disabled>​,
     * <input type=​"checkbox" id=​"withF103" name=​"withF103" onclick=​"marksChange()​;​">​,
     * <input type=​"checkbox" id=​"region" name=​"region" onclick=​"regionChange()​;​">​,
     * <input type=​"checkbox" id=​"book" name=​"book" onclick=​"bookChange()​;​">​]
     *
     *
     */


    protected $_urls = array(
        'Rate' => ''
    );

    protected $_possibleMethods;

    public function isTrackingAvailable()
    {
        return true;
    }

    protected function _isUkraine($countryId)
    {
        switch ($countryId) {
            case 'UA':
                return true;
                break;
        }

        return false;
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /**
         * http://services.ukrposhta.com/CalcUtil/Calculate.aspx?
         */

        $possibleMethods = $this->getPossibleMethods();
        if (!$possibleMethods) {
            return false;
        }

        $requestFields = array('switcher' => 'PostalMails');

        /**
         * @TODO handle following fields
         */
        $requestFields['book']   = 'false';
        $requestFields['region'] = 'false';
        $requestFields['postpay'] = 0;

        $requestFields['senderKind']      = $this->getConfigData('sender_kind');
        $requestFields['courierDostList'] = self::COURIER_NOT_DEFINED;

        /**
         * Set common flags
         */
        $flags = array(
            'withF103', 'withAddress', 'withForm',
            'packing', 'withMessenger', 'handPersonally',
            'isBulky', 'isFragile', 'withHanding'
        );
        foreach ($flags as $flag) {
            $requestFields[$flag] = $this->getConfigFlag($flag) ? self::FLAG_TRUE : self::FLAG_FALSE;
        }

        /**
         * Country handling
         */
        $countryId = $request->getDestCountryId();
        if ($this->_isUkraine($countryId)) {
            $requestFields['direction']      = self::DIRECTION_UKRAINE;
            $requestFields['country']        = self::COUNTRY_NOT_DEFINED;
            $requestFields['transferMethod'] = self::TRANSFER_METHOD_GROUND;
        } else {
            $requestFields['direction'] = self::DIRECTION_FOREIGN_COUNTRIES;

            /** @var Mage_Directory_Model_Country $country */
            $country = Mage::getModel('directory/country')
                ->load($countryId);
            if (!$country->getNumeric()) {
                return false;
            }

            $requestFields['country'] = $country->getNumeric();
            $requestFields['transferMethod'] = $this->getConfigData('transfer_type');
        }

        /**
         * Calculate package weight
         *
         * @TODO needed precision for letters
         */
        $requestFields['mass'] = ceil($request->getPackageWeight());
        $requestFields['massGramme'] = 0;

        $declaredValue = ceil(($request->getPackageValue() * (int)$this->getConfigData('declared_percent')) / 100);

        /** @var $result Mage_Shipping_Model_Rate_Result */
        $result = Mage::getModel('shipping/rate_result');

        foreach ($possibleMethods as $methodCode => $config) {
//
//            if ($methodCode == 'W_D') {
//                $e = 1;
//            }
            $methodFields = $config['fields'];

            if ($requestFields['direction'] == self::DIRECTION_FOREIGN_COUNTRIES
                && (in_array($config['shipment_type'], array('W', 'WP', 'LP'))
                && in_array($config['category'], array('D','R','S')))
                || ($config['shipment_type'] == 'L' && $config['category'] == 'D')
            ) {
                continue;
            }

            if (isset($methodFields['declaredValue'])) {
                $methodFields['declaredValue'] = $declaredValue;
            }

            $request = array_replace($requestFields, $methodFields);
            $price = $this->_getQuote($request);

            /** @var $method Mage_Shipping_Model_Rate_Result_Method */
            $method = Mage::getModel('shipping/rate_result_method');
            $method->setCarrier($this->_code)
                ->setCarrierTitle($this->getConfigData('name'))
                ->setMethod($methodCode)
                ->setMethodTitle($config['title'])
                ->setPrice($price)
                ->setCost($price);

            $result->append($method);
        }

        return $result;
    }

    protected function _getQuote($fields)
    {
        $apiUrl = $this->getConfigData('calc_api_url');
        $client = new Varien_Http_Client();
        $response = $client->setUri($apiUrl)
            ->setParameterGet($fields)
            ->setConfig(array('timeout' => 5))
            ->request('GET');

        $this->_debug(array(
            'url' => $apiUrl,
            'request' => $fields,
            'response' => $response->getBody()
        ));
        if (!$response->getBody()) {
            return false;
        }

        if (preg_match('/.*rightResultBold\"\>([0-9]+)\,([0-9]+)?.*\</', $response->getBody(), $matches)) {
            return ("$matches[1]" . '.' . "$matches[2]");
        }

        return false;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = array();
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('service', $k);
        }
        return $arr;
    }

    public function getPossibleMethods()
    {
        if (!$this->_possibleMethods) {
            $shipmentTypes = $this->getConfigData('shipment_type');
            if (empty($shipmentTypes)) {
                return false;
            }

            $allowedMethods = $this->getAllowedMethods();
            if (!$allowedMethods) {
                return false;
            }

            $regex = sprintf('/^(%s)\_(\w+)$/', implode('|', explode(',', $shipmentTypes)));
            foreach ($allowedMethods as $code => $title) {
                if (!preg_match($regex, $code, $matches)) {
                    continue;
                }
                $this->_possibleMethods[$code] = array(
                    'title' => $title,
                    'fields' => array_merge(
                        $this->getCode('shipment_type_fields', $matches[1]),
                        $this->getCode('category_fields', $matches[2])
                    ),
                    'shipment_type' => $matches[1],
                    'category' => $matches[2]
                );
            }
        }

        return $this->_possibleMethods;
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code = '')
    {
        static $codes;
        $helper = Mage::helper('ukraine/ukrposhta');
        $codes = array(
            'service' => array(
                'P_O'  => $helper->__('Ordinary Parcel'),
                'P_D'  => $helper->__('Declared Parcel'),
                'W_S'  => $helper->__('Simple Wrapper'),
                'W_R'  => $helper->__('Registered Wrapper'),
                'W_D'  => $helper->__('Declared Wrapper'),
                'WP_S' => $helper->__('Priority Simple Wrapper'),
                'WP_R' => $helper->__('Priority Registered Wrapper'),
                'WP_D' => $helper->__('Priority Declared Wrapper'),
                'L_S'  => $helper->__('Simple Letter'),
                'L_R'  => $helper->__('Registered Letter'),
                'L_D'  => $helper->__('Declared Letter'),
                'LP_S' => $helper->__('Priority Simple Letter'),
                'LP_R' => $helper->__('Priority Registered Letter'),
                'LP_D' => $helper->__('Priority Declared Letter')
            ),
            'shipment_type' => array(
                'L'  => $helper->__('Letter'),
                'LP' => $helper->__('Priority Letter'),
                'W'  => $helper->__('Wrapper'),
                'WP' => $helper->__('Priority Wrapper'),
                'P'  => $helper->__('Parcel'),
            ),
            'shipment_type_fields' => array(
                'L'  => array('mailKind' => self::TYPE_LETTER),
                'LP' => array('mailKind' => self::TYPE_LETTER_PRIORITY),
                'W'  => array('mailKind' => self::TYPE_WRAPPER),
                'WP' => array('mailKind' => self::TYPE_WRAPPER_PRIORITY),
                'P'  => array(
                    'mailKind' => self::TYPE_PARCEL,
                    'region'   => self::FLAG_FALSE
                )
            ),
            'category_fields' => array(
                'O' => array('mailCategory' => self::MAIL_CATEGORY_ORDINARY),
                'D' => array(
                    'mailCategory'       => self::MAIL_CATEGORY_DECLARED,
                    'declaredValue'      => 0,
                    'declaredValueCoins' => 0,
                    'courierDostList'    => self::COURIER_NOT_DEFINED
                ),
                'S' => array('mailCategory' => self::MAIL_CATEGORY_SIMPLE),
                'R' => array(
                    'mailCategory'    => self::MAIL_CATEGORY_REGISTERED,
                    'courierDostList' => self::COURIER_NOT_DEFINED
                ),
            ),
            'payer' => array(
                'M' => $helper->__('Merchant'),
                'B' => $helper->__('Buyer'),
            ),
            'transfer_type' => array(
                self::TRANSFER_METHOD_GROUND => $helper->__('Ground'),
                self::TRANSFER_METHOD_AIR    => $helper->__('Air'),
            ),
            'courier' => array(
                ''                              => $helper->__('Not Set'),
                self::COURIER_FROM_DOOR         => $helper->__('From Door'),
                self::COURIER_FROM_DOOR_TO_DOOR => $helper->__('From Door to Door'),
                self::COURIER_TO_DOOR           => $helper->__('To Door')
            ),
            'sender_kind' => array(
                self::SENDER_KIND_LEGAL_ENTITY   => $helper->__('Legal Entity'),
                self::SENDER_KIND_NATURAL_PERSON => $helper->__('Natural Person')
            )
        );


        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }
}