<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

/**
 * Was added for compatibility, but the Netherlands Antilles is not exists since 2011
 *
 * @link https://www.iso.org/obp/ui/#iso:code:3166:AN
 */
$numericCodes = array(
    'AN' => '530'
);

foreach ($numericCodes as $countryCode => $numericCode) {
    $installer->getConnection()->update(
        $installer->getTable('directory/country'),
        array('numeric' => (int)$numericCode),
        array('iso2_code = ?' => $countryCode)
    );
}