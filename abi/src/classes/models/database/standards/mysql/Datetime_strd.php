<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\database\standards\mysql;

use ABI\classes\Types;
use ABI\EventHandler;

class Datetime_strd {
	private static $pattern;

	private static function _checkCompatibility() {
		$datetime_regexp = "/^[1-9][0-9][0-9][0-9]-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/";

		if('false' === self::$pattern->getField('bind_db')) {
			if('true' === self::$pattern->getField('unique')) { EventHandler::validationError("The 'unique' property is supported only if the field is 'bind_db'"); }
		}

		if('' !== self::$pattern->getField('length')) { EventHandler::validationError("The 'length' property is not supported for 'Datetime' type"); }
		if('true' === self::$pattern->getField('ai')) { EventHandler::validationError("The 'auto_increment' property is not supported for 'Datetime' type"); }

		if(self::$pattern->getField('default') !== '') {
			if(!preg_match($datetime_regexp, self::$pattern->getField('default')) &&
			   self::$pattern->getField('default') !== Types::CURRENT_TIMESTAMP) {
				EventHandler::validationError("The value of 'default' parameter must match to the 'YYYY-MM-DD hh:mm:ss' pattern or to be 'CURRENT_TIMESTAMP'");
			}
		}

		if(self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) { EventHandler::validationError("The 'default' and 'require' parameters could not be included together"); }
		if(self::$pattern->getField('default') !== Types::CURRENT_TIMESTAMP && 'true' === self::$pattern->getField('unique')) { EventHandler::validationError("Can not include the 'unique' parameter with this value of 'default' parameter"); }
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

		if(Types::CURRENT_TIMESTAMP === $pattern->getField('default')) {
			$constValue['setConstValue'] = true;
			$constValue['constValue'] = date('Y-m-d h:i:s');
		}

		return $constValue;
	}
}
