<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\classes\Validator;
use ABI\EventHandler;
use ABI\Settings;

class Integer_strd {
	private static $pattern;

	private static function _mysqlTypeConverter() {
		$length_field = self::$pattern->getField('length');

		if	   ($length_field >= 1 	&& $length_field <= 2)	$driver_type = 'tinyint';
		elseif ($length_field >= 3 	&& $length_field <= 4) 	$driver_type = 'smallint';
		elseif ($length_field >= 5 	&& $length_field <= 6)	$driver_type = 'mediumint';
		elseif ($length_field >= 7	&& $length_field <= 9) 	$driver_type = 'int';
		elseif ($length_field >= 10 && $length_field <= 18) $driver_type = 'bigint';
		else EventHandler::validationError("The value of 'length' property must be in the range from 1 to 18");

		self::$pattern->setField('length', '');

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

		if('true' === self::$pattern->getField('bind_db')) {
			if(!is_integer(self::$pattern->getField('length'))) { EventHandler::validationError("Enter a value to box for entering 'length'"); }
		} else {
			if('true' === self::$pattern->getField('unique'))   { EventHandler::validationError("The 'unique' property is supported only if the field is 'bind_db'"); }
		}

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

		if(self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('unique'))  { EventHandler::validationError("The 'default' and 'unique' parameters could not be included together"); }
		if(self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('ai'))      { EventHandler::validationError("The 'default' and 'auto_increment' parameters could not be included together"); }
		if(self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) { EventHandler::validationError("The 'default' and 'require' parameters could not be included together"); }
		if('true' === self::$pattern->getField('require') && 'true'  === self::$pattern->getField('ai')) { EventHandler::validationError("The 'require' and 'auto_increment' parameters could not be included together"); }
		if('false' === self::$pattern->getField('unique') && 'true'  === self::$pattern->getField('ai')) { EventHandler::validationError("The parameter 'unique' is mandatory for the 'auto_increment' field"); }

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
