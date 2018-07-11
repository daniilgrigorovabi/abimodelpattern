<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes;

use ABI\classes\database\Database;
use ABI\classes\models\ABIModel_Param;
use ABI\EventHandler;
use ABI\Settings;

class Validator
{
	private static function setFieldType($field, $new_type)
    {
		// check type
		$new_type = Types::getDefaultType($new_type);
		settype($field, $new_type);
		return $field;
	}

	private static function checkFieldParams($value, $field_pattern)
    {
        $inst_class = Settings::getParam('custom_entities_ns') . '\\' . ucfirst($field_pattern->getField('type'));

	    if (!$value instanceof $inst_class) {
            // check needed type with type of request value
            Types::checkType($field_pattern->getField('type'), $value);

            // check the params for custom model
            self::checkRegExp($field_pattern->getField('pattern'), $value);
            self::checkLength($field_pattern->getField('length'), $value);

            // check the params for driver model
            if (Types::isDriverType($field_pattern->getField('type'))) {
                $driver_model = Parser::getModelPattern($field_pattern->getField('type'), true);
                $pattern = $driver_model[$field_pattern->getField('type')]->getField('pattern');
                $max_length = $driver_model[$field_pattern->getField('type')]->getField('length');

                self::checkRegExp($pattern, $value);
                self::checkLength($max_length, $value);
            }
        }
	}

	private static function checkRegExp($pattern, $value)
    {
		if ($pattern && !preg_match($pattern, $value)) {
			EventHandler::error("The pattern does not matches given value '$value'");
		}
	}

	private static function checkLength($max_length, $value)
    {
		if ($max_length && iconv_strlen($value,'UTF-8') > $max_length) {
			EventHandler::error("The length does not matches given value '$value'");
		}
	}

	private static function checkBoolParams($pattern)
    {
		$is_string_bool = array ('false', 'true');

		if (!in_array($pattern->getField('unique'), $is_string_bool)) {
		    EventHandler::validationError("The parameter 'unique' must be boolean type");
		}

		if (!in_array($pattern->getField('ai'), $is_string_bool)) {
		    EventHandler::validationError("The parameter 'ai' must be boolean type");
		}

		if (!in_array($pattern->getField('require'), $is_string_bool)) {
		    EventHandler::validationError("The parameter 'require' must be boolean type");
		}

		if (!in_array($pattern->getField('bind_db'), $is_string_bool)) {
		    EventHandler::validationError("The parameter 'bind_db' must be boolean type");
		}
	}

	public static function checkPatternField($value, $field_pattern)
    {
		// if field is 'Auto increment', set the 'null' value
		if ('true' === $field_pattern->getField('ai')) {
			$value = null;
		} else {
			// check params
			self::checkFieldParams($value, $field_pattern);
		}

		return $value;
	}

    public static function checkPatternFields($request_body, $pattern, $model_name)
    {
        $entity_ns = Settings::getParam('custom_entities_ns') . '\\' . ucfirst($model_name);
        $response = new $entity_ns($pattern);
        $request_body = (object) $request_body;

		foreach ($pattern as $field_name => $field_value) {
			// if field is 'Auto increment', set the 'null' value. Skip this iteration
			if ('true' === $field_value->getField('ai')) {
				$response->$field_name = null;
				continue;
			}

			// custom type
            if (Types::isCustomType($field_value->getField('type')) && isset($request_body->$field_name)) {
               $field_model_pattern = Parser::getModelPattern($field_value->getField('type'));
               $field_request_body = $request_body->$field_name;

               // push a field to the response body
               $response->$field_name = Validator::checkPatternFields(
                   $field_request_body,
                   $field_model_pattern,
                   $field_value->getField('type')
               );
               continue;
			}

			$standard = self::getStandard($field_value->getField('type'));
			$constValue = $standard::getConstValue($field_value);

			if ($constValue['setConstValue']) {
			    $request_body->$field_name = $constValue['constValue'];
			}

			// field absent in the request body
			if (!isset($request_body->$field_name)) {
				if ($field_value->getField('require') === 'true') {
				    EventHandler::error("The required field '$field_name' was not found in request body");
				}

				if ($field_value->getField('unique')  === 'true') {
				    EventHandler::error("Cannot set the field '$field_name' value as default value. This field is required");
				}

                // create field. Set the needed type for default field
                if (Types::isCustomType($field_value->getField('type'))) {
                    $entity_custom_field_ns = Settings::getParam('custom_entities_ns') . '\\' . ucfirst($field_value->getField('type'));
                    $custom_field_model_pattern = Parser::getModelPattern($field_value->getField('type'));
                    $custom_field_response = new $entity_custom_field_ns($custom_field_model_pattern);
                    $request_body->$field_name = $custom_field_response;
                } else {
                    $field_value->setField('default', self::setFieldType(
                        $field_value->getField('default'),
                        $field_value->getField('type')
                    ));
                    $request_body->$field_name = $field_value->getField('default');
                }

                // push a field to the response body
                $response->$field_name = $request_body->$field_name;
                continue;
			}

			// type collection
            if ($field_value->getField('collection')) {
                Types::checkType($field_value->getField('type'), $request_body->$field_name);
                Types::checkCollectionType(gettype($request_body->$field_name));

                if (Types::isCustomType($field_value->getField('collection'))) {
                    $field_model_pattern = Parser::getModelPattern($field_value->getField('collection'));
                    foreach ($request_body->$field_name as $key => $value) {
                        $new_collection[] = Validator::checkPatternFields(
                            $value,
                            $field_model_pattern,
                            $field_value->getField('collection')
                        );
                    }
                } else {
                    EventHandler::error(
                        'The given type \'' . $field_value->getField('collection') .
                        '\' is not accessible by collection'
                    );
                }

                settype($new_collection, $field_value->getField('type'));

                // push a field to the response body
                $response->$field_name = $new_collection;
                continue;
            }

            // push a field to response body
            $response->$field_name = $request_body->$field_name;
		}

		return $response;
	}

	public static function getStandard($standardName)
    {
		$standard_class = Types::getStandardClass($standardName);

		if ('default' === $standard_class) {
			$standard = Settings::getParam('custom_standards_ns') . ucfirst($standardName) .
                Settings::getParam('standards_postfix');
		} elseif ('driver' === $standard_class) {
			$standard = Settings::getParam('driver_standards_ns') . Settings::getParam('driver') . '\\' .
                ucfirst($standardName) . Settings::getParam('standards_postfix');
		} elseif ('custom' === $standard_class) {
            $standard = Settings::getParam('custom_standards_ns') . ucfirst($standard_class) .
                Settings::getParam('standards_postfix');
        } else {
			EventHandler::error('The given type ' . $standardName . ' not found or unavailable');
		}

		return $standard;
	}

	public static function checkModelFieldStructure(ABIModel_Param $pattern, $standard, $isOldModel = false)
    {
		self::checkBoolParams($pattern);
		$standard::checkStandard($pattern, $isOldModel);
	}

	public static function checkApplicationStatus()
    {
		$result = array (
			'databaseSettings'  => array (
				'status'  => 'success',
				'message' => 'Connection to database has been established. You can choose which model fields will be created in the DB'
			),
			'loggerSettings'    => array (
				'status'  => 'success',
				'message' => 'System logger is fully customized. You can update parameters in the "Logger" page'
			),
			'patternFolderPerm' => array (
				'status'  => 'success',
				'message' => 'Properties of the folder for custom models is set up correctly. You can create models in the "My models" page'
			),
			'settingsFilePerm'  => array (
				'status'  => 'success',
				'message' => 'You can view and manage your preferences connection to database and system logger in the relevant pages'
			)
		);

		// check permission for pattern directory
		if (
		    substr(sprintf('%o', fileperms(Parser::getPath('custom_patterns_rf'))),-4) < '0777' ||
            substr(sprintf('%o', fileperms(Parser::getPath('entity_standard_rf'))),-4) < '0777'
        ) {
			$result['patternFolderPerm']['status']  = 'error';
            $result['patternFolderPerm']['message'] = 'Permission Denied. You have to change access restrictions for the \'' .
                Parser::getPath('custom_patterns_rf') . '\' and \'' .
                Parser::getPath('entity_standard_rf') . '\' folders to 0777 to full use this library';
        }

		// check permission for config file
		if (substr(sprintf('%o', fileperms(Parser::getPath('config_rf'))), -4) < '0666') {
			$result['settingsFilePerm']['status']  = 'error';
			$result['settingsFilePerm']['message'] = 'Permission Denied. You have to change access restrictions for the \'' .
                Parser::getPath('config_rf') . '\' file to 0666 to full use this library';
		}

		// check parameters for database settings
        if ('true' !== Settings::getParam('is_db_enable')) {
            $result['databaseSettings']['status']  = 'warning';
            $result['databaseSettings']['message'] = 'The connection to database is disabled. You can update the settings in the "Database" page';
        } else {
            try {
                new Database();
            } catch (\Exception $e) {
                $result['databaseSettings']['status'] = 'warning';
                $result['databaseSettings']['message'] = 'Cannot connect to database. Check that your settings to connection are entered correctly';
            }
        }

        // check parameters for logger
        if ('true' !== Settings::getParam('is_logging_enable')) {
            $result['loggerSettings']['status']  = 'warning';
            $result['loggerSettings']['message'] = 'The functionality of logging is disabled. You can update the settings in the "Logger" page';
        } else {
            $logger_params = array(
                'logfile_name' => Settings::getParam('logfile_name'),
                'logfile_create_after_hours' => Settings::getParam('logfile_create_after_hours'),
                'log_level' => Settings::getParam('log_level'),
                'log_path' => Settings::getParam('log_path')
            );

            if (in_array('', $logger_params)) {
                $result['loggerSettings']['status'] = 'warning';
                $result['loggerSettings']['message'] = 'Some parameters of system logger are used by default. You can update them in the "Logger" page';
            }
        }

		return $result;
	}
}
