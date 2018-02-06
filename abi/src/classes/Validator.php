<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes;

use ABI\classes\database\Database;
use ABI\classes\models\ABIModel_Param;
use ABI\classes\models\FinalModel;
use ABI\EventHandler;
use ABI\Settings;

class Validator {

	private static function _setFieldType($field, $new_type) {
		// Check type
		$new_type = Types::getDefaultType($new_type);
		settype($field, $new_type);
		return $field;
	}

	private static function _checkFieldParams($value, $field_pattern) {
		// Check needed type with type of request value
        Types::checkType($field_pattern->getField('type'), $value);

		// Check the params for custom model
		self::_checkRegExp($field_pattern->getField('pattern'), $value);
		self::_checkLength($field_pattern->getField('length'), $value);

		// Check the params for driver model
		if(Types::isDriverType($field_pattern->getField('type'))) {
			$driver_model = Parser::getModelPattern($field_pattern->getField('type'), true);
			$pattern    = $driver_model[$field_pattern->getField('type')]->getField('pattern');
			$max_length = $driver_model[$field_pattern->getField('type')]->getField('length');

			self::_checkRegExp($pattern, $value);
			self::_checkLength($max_length, $value);
		}
	}

	private static function _checkRegExp($pattern, $value) {
		if($pattern && !preg_match($pattern, $value)) {
			EventHandler::error("The pattern does not matches given value '$value'");
		}
	}

	private static function _checkLength($max_length, $value) {
		if($max_length && iconv_strlen($value,'UTF-8') > $max_length) {
			EventHandler::error("The length does not matches given value '$value'");
		}
	}

	private static function _checkBoolParams($pattern) {
		$is_string_bool = array('false', 'true');

		if(!in_array($pattern->getField('unique'),  $is_string_bool)) { EventHandler::validationError("The parameter 'unique' must be boolean type");  }
		if(!in_array($pattern->getField('ai'), 		$is_string_bool)) { EventHandler::validationError("The parameter 'ai' must be boolean type");      }
		if(!in_array($pattern->getField('require'), $is_string_bool)) { EventHandler::validationError("The parameter 'require' must be boolean type"); }
		if(!in_array($pattern->getField('bind_db'), $is_string_bool)) { EventHandler::validationError("The parameter 'bind_db' must be boolean type"); }
	}

	public static function checkPatternField($value, $field_pattern) {
		// If field is 'Auto increment', set the 'null' value
		if('true' === $field_pattern->getField('ai')) {
			$value = null;
		} else {
			// Check params
			self::_checkFieldParams($value, $field_pattern);
		}

		return $value;
	}

	public static function checkPatternFields($request_body, $pattern) {
		$response = new FinalModel($pattern);

		foreach ($pattern as $field_name => $field_value) {
			// If field is 'Auto increment', set the 'null' value. Skip this iteration
			if('true' === $field_value->getField('ai')) {
				$response->$field_name = null;
				continue;
			}

			$standard = self::getStandard($field_value->getField('type'));
			$constValue = $standard::getConstValue($field_value);
			if($constValue['setConstValue']) { $request_body->$field_name = $constValue['constValue']; }

			// Field absent in request body
			if(!isset($request_body->$field_name)) {
				if($field_value->getField('require') === 'true') { EventHandler::error("The required field '$field_name' was not found in request body"); }
				if($field_value->getField('unique')  === 'true') { EventHandler::error("Cannot set the field '$field_name' value as default value. This field is required"); }

				// Create field. Set the needed type for default field
				$field_value->setField('default', self::_setFieldType($field_value->getField('default'), $field_value->getField('type')));
				$request_body->$field_name = $field_value->getField('default');
			}

			// Push a field to response body
			$response->$field_name = $request_body->$field_name;
		}

		return $response;
	}

	public static function getStandard($standardName) {
		$standard_class = Types::getStandardClass($standardName);

		if('default' === $standard_class) {
			$standard = Settings::getParam('custom_standards_ns').ucfirst($standardName).
						Settings::getParam('standards_postfix');

		} else if('driver' === $standard_class) {
			$standard = Settings::getParam('driver_standards_ns'). Settings::getParam('driver').
						'\\'.ucfirst($standardName).Settings::getParam('standards_postfix');
		} else {
			EventHandler::error('The given type '.$standardName.' not found or unavailable');
		}

		return $standard;
	}

	public static function checkModelFieldStructure(ABIModel_Param $pattern, $standard) {
		self::_checkBoolParams($pattern);
		$standard::checkStandard($pattern);
	}

	public static function checkApplicationStatus() {
		$result = array(
			'databaseSettings'  => array(
				'status'  => 'success',
				'message' => 'Connection to database has been established. You may сhoosing which model fields  will be created in the DB'
			),
			'loggerSettings'    => array(
				'status'  => 'success',
				'message' => 'System logger is fully customized. You may update it parameters in the "Logger" page'
			),
			'patternFolderPerm' => array(
				'status'  => 'success',
				'message' => 'Properties of the folder for custom models is set up correctly. You may create models in the "My models" page'
			),
			'settingsFilePerm'  => array(
				'status'  => 'success',
				'message' => 'You may view, and manage your preferences connection to database and system logger in the relevant pages'
			)
		);

		// Check permission for pattern directory
		if(substr(sprintf('%o', fileperms(Parser::getPath('custom_patterns_rf'))),-4) < '0777') {
			$result['patternFolderPerm']['status']  = 'error';
            $result['patternFolderPerm']['message'] = 'Permission Denied. You have to change access restrictions for the \''.Parser::getPath('custom_patterns_rf').'\' folder to 0777 to full use this library';
        }

		// Check permission for config file
		if(substr(sprintf('%o', fileperms(Parser::getPath('config_rf'))), -4) < '0666') {
			$result['settingsFilePerm']['status']  = 'error';
			$result['settingsFilePerm']['message'] = 'Permission Denied. You have to change access restrictions for the \''.Parser::getPath('config_rf').'\' file to 0666 to full use this library';
		}

		// Check parameters for database settings
		try {
            new Database();
        } catch (\Exception $e) {
            $result['databaseSettings']['status']  = 'warning';
            $result['databaseSettings']['message'] = 'Cannot connect to database. Check that your settings to connection are entered correctly';
        }

        // Check parameters for logger
		$logger_params = array(
			'logfile_name' => Settings::getParam('logfile_name'),
			'logfile_create_after_hours' => Settings::getParam('logfile_create_after_hours'),
			'log_level' => Settings::getParam('log_level'),
			'log_path' => Settings::getParam('log_path')
		);

		if(in_array('', $logger_params)) {
			$result['loggerSettings']['status']  = 'warning';
            $result['loggerSettings']['message'] = 'Some parameters of system logger are used by default. You may update them in the "Logger" page';
        }

		return $result;
	}

}
