<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\EventHandler;
use ABI\Settings;

class Boolean_strd {
	private static $pattern;

	private static function _mysqlTypeConverter() {
		$driver_type = 'tinyint';

		self::$pattern->setField('length', 1);

		if(self::$pattern->getField('default') !== '') {
			$default_value = self::$pattern->getField('default');
			settype($default_value, self::$pattern->getField('type'));
			self::$pattern->setField('default', $default_value);
		}

		return $driver_type;
	}

	private static function _converterType() {
		$driver_method_name = '_'.Settings::getParam('driver').'TypeConverter';
		if(!method_exists(self::class, $driver_method_name)) { EventHandler::error('The driver was not found for convert default type'); }
		self::$pattern->setField('type', self::$driver_method_name());
	}

	private static function _checkCompatibility() {
		$is_string_bool = array('false', 'true');

		if('true'  === self::$pattern->getField('unique')) { EventHandler::validationError("The 'unique' property is not supported for 'Boolean' type"); }
		if(''      !== self::$pattern->getField('length')) { EventHandler::validationError("The 'length' property is not supported for 'Boolean' type"); }
		if('true'  === self::$pattern->getField('ai'))     { EventHandler::validationError("The 'auto_increment' property is not supported for 'Boolean' type"); }

		if(self::$pattern->getField('default') !== '') {
			if (!in_array(self::$pattern->getField('default'), $is_string_bool)) {
                EventHandler::validationError("The value of 'default' parameter must equals 'true' or 'false'");
			}
		}

		if(self::$pattern->getField('default') !== '' && 'true'  === self::$pattern->getField('require')) {EventHandler::validationError("The 'default' and 'require' parameters could not be included together"); }
		if(self::$pattern->getField('default') === '' && 'false' === self::$pattern->getField('require')) {EventHandler::validationError("The 'require' or 'default' parameter must be included"); }
	}

	public static function checkStandard($pattern) {
		self::$pattern = $pattern;
		self::_checkCompatibility();

		if('true' === self::$pattern->getField('bind_db')) {
			self::_converterType();
		}
	}

	public static function getConstValue($pattern) {
		$constValue = array(
			'setConstValue' => false,
			'constValue'	=> ''
		);

		return $constValue;
	}
}