<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\classes\Types;
use ABI\EventHandler;

class Object_strd {
	private static $pattern;

	private static function _checkCompatibility() {
		if('true'  === self::$pattern->getField('bind_db')) { EventHandler::validationError("The 'bind_db' property is not supported for 'Object' type"); }
		if('true'  === self::$pattern->getField('unique'))  { EventHandler::validationError("The 'unique' property is not supported for 'Object' type"); }
		if(''      !== self::$pattern->getField('length'))  { EventHandler::validationError("The 'length' property is not supported for 'Object' type"); }
		if('true'  === self::$pattern->getField('ai'))      { EventHandler::validationError("The 'auto_increment' property is not supported for 'Object' type"); }
		if('true'  === self::$pattern->getField('require') && self::$pattern->getField('default') !== '') { EventHandler::validationError("The 'default' and 'require' parameters could not be included together"); }
		if('false' === self::$pattern->getField('require') && self::$pattern->getField('default') !== Types::EMPTY_OBJECT) { EventHandler::validationError("The 'default' value must be 'EMPTY' if the 'require' property is not included"); }
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

		if(Types::EMPTY_OBJECT === $pattern->getField('default')) {
			$pattern->setField('default', '');
		}

		return $constValue;
	}
}
