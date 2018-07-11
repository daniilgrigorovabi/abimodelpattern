<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\classes\Validator;
use ABI\EventHandler;

class Double_strd
{
	private static $pattern;

	private static function checkCompatibility()
    {
		if ('true' === self::$pattern->getField('bind_db')) {
		    EventHandler::validationError("The 'bind_db' property is not supported for 'Double' type");
		}

		if ('true' === self::$pattern->getField('unique')) {
		    EventHandler::validationError("The 'unique' property is not supported for 'Double' type");
		}

		if ('true' === self::$pattern->getField('ai')) {
		    EventHandler::validationError("The 'auto_increment' property is not supported for 'Double' type");
		}

		if (self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) {
		    EventHandler::validationError("The 'default' and 'require' parameters could not be included together");
		}

		if (self::$pattern->getField('default') === '' && 'false' === self::$pattern->getField('require')) {
		    EventHandler::validationError("The 'require' or 'default' parameter must be included");
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

        // TODO: beta version
        if ('' !== self::$pattern->getField('collection')) {
            EventHandler::validationError("The 'collection' property is not supported for 'Double' type");
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
