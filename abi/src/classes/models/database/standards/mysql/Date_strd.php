<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models\database\standards\mysql;

use ABI\classes\Types;
use ABI\EventHandler;

class Date_strd
{
	private static $pattern;

	private static function checkCompatibility()
    {
		$date_regexp = "/^[1-9][0-9][0-9][0-9]-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";

		if ('false' === self::$pattern->getField('bind_db')) {
			if ('true' === self::$pattern->getField('unique')) {
			    EventHandler::validationError("The 'unique' property is supported only if the field is 'bind_db'");
			}
		}

		if ('' !== self::$pattern->getField('length')) {
		    EventHandler::validationError("The 'length' property is not supported for 'Date' type");
		}

		if ('true' === self::$pattern->getField('ai')) {
		    EventHandler::validationError("The 'auto_increment' property is not supported for 'Date' type");
		}

		if (self::$pattern->getField('default') !== '') {
			if (
			    !preg_match($date_regexp, self::$pattern->getField('default')) &&
                self::$pattern->getField('default') !== Types::CURRENT_TIMESTAMP
            ) {
			    EventHandler::validationError("The value of 'default' parameter must match to the 'YYYY-MM-DD' pattern or to be 'CURRENT_TIMESTAMP'");
			}
		}

		if (self::$pattern->getField('default') !== '' && 'true' === self::$pattern->getField('require')) {
		    EventHandler::validationError("The 'default' and 'require' parameters could not be included together");
		}

		if (
		    self::$pattern->getField('default') !== Types::CURRENT_TIMESTAMP &&
            'true' === self::$pattern->getField('unique')
        ) {
		    EventHandler::validationError("Can not include the 'unique' parameter with this value of 'default' parameter");
		}

        // TODO: beta version
        if ('' !== self::$pattern->getField('collection')) {
            EventHandler::validationError("The 'collection' property is not supported for 'Date' type");
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

		if (Types::CURRENT_TIMESTAMP === $pattern->getField('default')) {
			$constValue['setConstValue'] = true;
			$constValue['constValue'] = date('Y-m-d');
		}

		return $constValue;
	}
}
