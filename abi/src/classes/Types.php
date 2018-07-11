<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes;

use ABI\EventHandler;
use ABI\Settings;

class Types
{
    const EMPTY_ARRAY       = 'EMPTY';
    const EMPTY_OBJECT      = 'EMPTY';
    const EMPTY_CUSTOM      = 'EMPTY';
    const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

	private static function checkDefaultType($type)
    {
		$type = mb_strtolower($type);
		$available_types = self::getAvailableTypes();
		$available_types = $available_types['default'];

		if (!in_array($type, $available_types)) {
            EventHandler::error('The type of field does not match model pattern type');
		}
	}

	private static function getTypesByClass($path_types_class)
    {
		$available_types = array();
		$types = array_diff(scandir($path_types_class), array ('..', '.'));

		foreach ($types as $type) {
			$file_name_arr = explode('.', $type);
			$file_extension = array_pop($file_name_arr);

			if ('php' === $file_extension) {
				$file_name = implode('.', $file_name_arr);
				$file_name = str_replace(Settings::getParam('standards_postfix'), '', $file_name);
				$available_types[] = strtolower($file_name);
			}
		}

		return $available_types;
	}

	public static function checkType($type, $value)
    {
		$type = self::getDefaultType($type);

		if ($type != lcfirst(gettype($value))) {
            EventHandler::error('The type of value does not match the pattern of the type');
		}
	}

	public static function checkCollectionType($type)
    {
	    if ('object' !== $type && 'array' !== $type) {
            EventHandler::error('The collection type can be stored in array or object');
        }
    }

	public static function getDefaultType($type)
    {
		$type = mb_strtolower($type);
		$available_types = self::getAvailableTypes();
		$available_types = $available_types['driver'];

		if (in_array($type, $available_types)) {
			$driver_pattern = Parser::getModelPattern($type, true);
			$type = $driver_pattern[$type]->getField('type');
		}

		self::checkDefaultType($type);
		return $type;
	}

	public static function isDriverType($type)
    {
		$is_driver_type = false;
		$available_types = self::getAvailableTypes();
		$available_types = $available_types['driver'];

		if (in_array($type, $available_types)) {
			$is_driver_type = true;
		}

		return $is_driver_type;
	}

    public static function isCustomType($type)
    {
        $is_custom_type = false;
        $available_types = self::getAvailableTypes();
        $available_types = $available_types['custom'];

        if (in_array($type, $available_types)) {
            $is_custom_type = true;
        }

        return $is_custom_type;
    }

    public static function isDefaultType($type)
    {
        $is_custom_type = false;
        $available_types = self::getAvailableTypes();
        $available_types = $available_types['default'];

        if (in_array($type, $available_types)) {
            $is_custom_type = true;
        }

        return $is_custom_type;
    }

	public static function isValidModelName($model_name, $isUpdate = false)
    {
		// check name of model (xml file)
		if (!preg_match(Settings::getParam('pattern_model_name'), $model_name)) {
            EventHandler::validationError("The name of model '$model_name' unavailable");
		}

		$model_name = mb_strtolower($model_name);
        $unavailable_names = self::getUnavailableNames();

        if (true === $isUpdate && array_key_exists('custom', $unavailable_names)) {
            unset($unavailable_names['custom']);
        }

		foreach ($unavailable_names as $unavailable_name) {
			if (in_array($model_name, $unavailable_name)) {
                EventHandler::error("The name of model '$model_name' is reserved");
			}
		}
	}

	public static function isValidFieldName($field_name)
    {
		// check name of field
        if ('_empty_' === $field_name) {
            EventHandler::validationError('Enter a valid name of a field');
        }

		if (!preg_match(Settings::getParam('pattern_field_name'), $field_name)) {
            EventHandler::validationError("The name of field '$field_name' unavailable");
		}

        $field_name = mb_strtolower($field_name);

        $unavailable_names = self::getUnavailableNames();

        if (array_key_exists('custom', $unavailable_names)) {
            unset($unavailable_names['custom']);
        }

        foreach ($unavailable_names as $unavailable_name) {
            if (in_array($field_name, $unavailable_name)){
                EventHandler::validationError("The name of field '$field_name' is reserved");
            }
        }
	}

    public static function getUnavailableNames()
    {
        $unavailable_names = self::getAvailableTypes();

        // TODO: check active driver
        $unavailable_names['third_party'] = array (
            'int'   , 'tinyint'   , 'smallint', 'mediumint' , 'bigint'         , 'decimal'     , 'real',
            'serial', 'timestamp' , 'time'    , 'year'      , 'char'           , 'text'        , 'tinytext',
            'binary', 'varbinary' , 'blob'    , 'tinyblob'  , 'mediumblob'     , 'longblob'    , 'enum',
            'point' , 'linestring', 'polygon' , 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection',
            'json'  , 'bool'      , 'longtext', 'geometry'  , 'mediumtext'     , 'bit'         , 'set',
            'custom'
        );

        return $unavailable_names;
    }

	public static function getAvailableTypes()
    {
		// default types
		$available_types['default'] = self::getTypesByClass(Parser::getPath('custom_standards_rf'));

		// driver types
		$available_types['driver'] = self::getTypesByClass(
		    Parser::getPath('driver_standards_rf') . Settings::getParam('driver')
        );

        // custom types
        $available_types['custom'] = self::getTypesByClass(Parser::getPath('entity_standard_rf'));

		$available_types = array_filter($available_types);
		return $available_types;
	}

	public static function getStandardClass($type)
    {
		$available_types = self::getAvailableTypes();

		foreach ($available_types as $current_class => $available_type) {
			if (in_array($type, $available_type)) {
				$standard_class = $current_class;
				break;
			}
		}

		if (!isset($standard_class)) {
			EventHandler::error("The given type '$type' not found");
		}

		return $standard_class;
	}
}
