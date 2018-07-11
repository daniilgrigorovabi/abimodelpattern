<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\custom\standards;

use ABI\classes\Types;
use ABI\EventHandler;

class Array_strd
{
	private static $pattern;

	private static function checkCompatibility()
    {
		if ('true' === self::$pattern->getField('bind_db')) {
		    EventHandler::validationError("The 'bind_db' property is not supported for 'Array' type");
		}

		if ('true' === self::$pattern->getField('unique')) {
		    EventHandler::validationError("The 'unique' property is not supported for 'Array' type");
		}

		if ('' !== self::$pattern->getField('length')) {
		    EventHandler::validationError("The 'length' property is not supported for 'Array' type");
		}

		if ('true' === self::$pattern->getField('ai')) {
		    EventHandler::validationError("The 'auto_increment' property is not supported for 'Array' type");
		}

		if ('true' === self::$pattern->getField('require') && self::$pattern->getField('default') !== '') {
		    EventHandler::validationError("The 'default' and 'require' parameters could not be included together");
		}

		if ('false' === self::$pattern->getField('require') && self::$pattern->getField('default') !== Types::EMPTY_ARRAY) {
		    EventHandler::validationError("The 'default' value must be 'EMPTY' if the 'require' property is not included");
		}

		// TODO: beta version
		if ('' !== self::$pattern->getField('collection') && !Types::isCustomType(self::$pattern->getField('collection'))) {
            EventHandler::validationError("The 'collection' value is not valid");
        }
	}

	public static function checkStandard($pattern, $isOldModel = false)
    {
		self::$pattern = $pattern;
		self::checkCompatibility();
	}

	public static function getConstValue($pattern)
    {
		$constValue = array (
			'setConstValue' => false,
			'constValue'	=> ''
		);

		if (Types::EMPTY_ARRAY === $pattern->getField('default')) {
			$pattern->setField('default', null);
		}

		return $constValue;
	}
}
