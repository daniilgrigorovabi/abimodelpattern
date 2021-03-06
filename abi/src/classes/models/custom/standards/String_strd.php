<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\classes\Validator;
use ABI\EventHandler;
use ABI\Settings;

class String_strd
{
	private static $pattern;

	private static function mysqlTypeConverter()
    {
		$length_field = self::$pattern->getField('length');

		if ($length_field >= 1 && $length_field <= 64) {
		    $driver_type = 'tinytext';
        } elseif ($length_field >= 65 && $length_field <= 16383) {
		    $driver_type = 'text';
        } elseif ($length_field >= 16384 && $length_field <= 4194303) {
		    $driver_type = 'mediumtext';
        } elseif ($length_field >= 4194304 && $length_field <= 1073741823) {
		    $driver_type = 'longtext';
        } else {
		    EventHandler::validationError("The value of 'length' property must be in the range from 1 to 1073741823");
        }

        // text can't have a length value
		self::$pattern->setField('length', '');
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
		if ('true' === self::$pattern->getField('bind_db')) {
			if (!is_integer(self::$pattern->getField('length'))) {
			    EventHandler::validationError("Enter a value to box for entering 'length'");
			}

			if (self::$pattern->getField('default') !== '')	{
			    EventHandler::validationError("The 'default' property is supported only if the field is not 'bind_db'");
			}
		}

		if (self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) {
		    EventHandler::validationError("The 'default' and 'require' parameters could not be included together");
		}

		if ('true' === self::$pattern->getField('unique')) {
		    EventHandler::validationError("The 'unique' property is not supported for 'String' type");
		}

		if (self::$pattern->getField('default') !== '') {
			try {
                Validator::checkPatternField(self::$pattern->getField('default'), self::$pattern);
			} catch (\Exception $e) {
				EventHandler::validationError("The default value is not valid");
			}
		}

		if (self::$pattern->getField('length') !== '') {
			if (!is_integer(self::$pattern->getField('length'))) {
			    EventHandler::validationError("The parameter 'length' must be integer type");
			}

			if (self::$pattern->getField('length') < 1) {
			    EventHandler::validationError("The value of 'length' property must be greater than 0");
			}

			if (iconv_strlen(self::$pattern->getField('default')) > self::$pattern->getField('length')) {
			    EventHandler::validationError("Count of characters in the 'default' value more than allowed in the 'length' parameter");
			}
		}

		if ('true' === self::$pattern->getField('ai')) {
		    EventHandler::validationError("The 'auto_increment' property is not supported for 'String' type");
		}

        // TODO: beta version
        if ('' !== self::$pattern->getField('collection')) {
            EventHandler::validationError("The 'collection' property is not supported for 'String' type");
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
