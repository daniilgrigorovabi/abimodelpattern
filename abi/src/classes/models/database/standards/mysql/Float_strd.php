<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\database\standards\mysql;

use ABI\classes\Validator;
use ABI\EventHandler;

class Float_strd {
    private static $pattern;

    private static function _checkCompatibility() {

        if('true' === self::$pattern->getField('bind_db')) {
            if(!is_integer(self::$pattern->getField('length'))) { EventHandler::validationError("Enter a value to box for entering 'length'"); }
            if(self::$pattern->getField('length') < 1 || self::$pattern->getField('length') > 24) { EventHandler::validationError("The value of 'length' property must be in the range from 1 to 24"); }
        } else {
            if('true' === self::$pattern->getField('unique'))   { EventHandler::validationError("The 'unique' property is supported only if the field is 'bind_db'"); }
        }

        if(self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) { EventHandler::validationError("The 'default' and 'require' parameters could not be included together"); }
        if(self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('unique'))  { EventHandler::validationError("The 'default' and 'unique' parameters could not be included together"); }

        if('true' === self::$pattern->getField('ai')) { EventHandler::validationError("The 'auto_increment' property is not supported for 'Float' type"); }

		if(self::$pattern->getField('default') !== '') {
			try {
                Validator::checkPatternField(self::$pattern->getField('default'), self::$pattern);
			} catch (\Exception $e) {
				EventHandler::validationError("The default value is not valid");
			}
		}

        if(self::$pattern->getField('length') !== '') {
            if(!is_integer(self::$pattern->getField('length'))) { EventHandler::validationError("The parameter 'length' must be integer type"); }
            if(self::$pattern->getField('length') < 1) { EventHandler::validationError("The value of 'length' property must be greater than 0"); }
            if(iconv_strlen(self::$pattern->getField('default')) > self::$pattern->getField('length')) { EventHandler::validationError("Count of characters in the 'default' value more than allowed in the 'length' parameter"); }
        }

    }

    public static function checkStandard($pattern) {
        self::$pattern = $pattern;
        self::_checkCompatibility();
    }

    public static function getConstValue($pattern) {
        $constValue = array(
            'setConstValue' => false,
            'constValue'	=> ''
        );

        return $constValue;
    }
}