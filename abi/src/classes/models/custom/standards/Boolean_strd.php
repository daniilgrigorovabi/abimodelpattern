<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\classes\Types;
use ABI\EventHandler;
use ABI\Settings;

class Boolean_strd
{
	private static $pattern;

	private static function mysqlTypeConverter()
    {
		$driver_type = 'tinyint';
		self::$pattern->setField('length', 1);

		if (self::$pattern->getField('default') !== '') {
			$default_value = self::$pattern->getField('default');
			settype($default_value, self::$pattern->getField('type'));
			self::$pattern->setField('default', $default_value);
		}

		return $driver_type;
	}

	private static function converterType()
    {
		$driver_method_name = Settings::getParam('driver') . 'TypeConverter';

		if (!method_exists(self::class, $driver_method_name)) {
		    EventHandler::error('The driver was not found for convert default type');
		}

		self::$pattern->setField('type', self::$driver_method_name());
	}

	private static function checkCompatibility()
    {
		$is_string_bool = array ('false', 'true');

		if ('true' === self::$pattern->getField('unique')) {
		    EventHandler::validationError("The 'unique' property is not supported for 'Boolean' type");
		}

		if ('' !== self::$pattern->getField('length')) {
		    EventHandler::validationError("The 'length' property is not supported for 'Boolean' type");
		}

		if ('true' === self::$pattern->getField('ai')) {
		    EventHandler::validationError("The 'auto_increment' property is not supported for 'Boolean' type");
		}

		if (self::$pattern->getField('default') !== '') {
			if (!in_array(self::$pattern->getField('default'), $is_string_bool)) {
                EventHandler::validationError("The value of 'default' parameter must equals 'true' or 'false'");
			}
		}

		if (self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) {
		    EventHandler::validationError("The 'default' and 'require' parameters could not be included together");
		}

		if (self::$pattern->getField('default') === '' && 'false' === self::$pattern->getField('require')) {
		    EventHandler::validationError("The 'require' or 'default' parameter must be included");
		}

        // TODO: beta version
        if ('' !== self::$pattern->getField('collection')) {
            EventHandler::validationError("The 'collection' property is not supported for 'Boolean' type");
        }
	}

	public static function checkStandard($pattern, $isOldModel = false)
    {
		self::$pattern = $pattern;

        // create field. Set the needed type for default field
        if (true === $isOldModel && '' !== $pattern->getField('default')) {
            $new_type = Types::getDefaultType($pattern->getField('type'));
            $new_default = $pattern->getField('default');
            settype($new_default, $new_type);
            $pattern->setField('default', $new_default);
        }

		self::checkCompatibility();

		if ('true' === self::$pattern->getField('bind_db')) {
			self::converterType();
		}
	}

	public static function getConstValue($pattern)
    {
		$constValue = array (
			'setConstValue' => false,
			'constValue'	=> ''
		);

		return $constValue;
	}
}
