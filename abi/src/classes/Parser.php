<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes;

use ABI\classes\database\Database;
use ABI\classes\models\ABIModel_Param;
use ABI\classes\models\Params;
use ABI\EventHandler;
use ABI\Settings;

class Parser
{
	private static function deleteAttrFromTag($startTag)
    {
		$arrOpenTag = explode(' ', $startTag);
		$openEmptyTag = $arrOpenTag[0];
		$arrOpenEmptyTag = explode('>', $openEmptyTag);
		$emptyTag = $arrOpenEmptyTag[0];
		return $emptyTag;
	}

	private static function retrieveTagsFromXML($msg, $flag = PREG_PATTERN_ORDER)
    {
		preg_match_all("|<[^\<\>]+>|U", $msg, $arrXMLTags, $flag);
		return $arrXMLTags;
	}

	private static function getAttrValue($lastTagFullName)
    {
		preg_match_all('~<(?:.*?)value="(.*?)"(?:.*?)>~s', $lastTagFullName, $value_arr, PREG_SET_ORDER);

		if ($value_arr) {
			$value = $value_arr[0][1];
		} else {
            EventHandler::error("Not found a value in the '$lastTagFullName' tag");
		}

		return $value;
	}

	private static function deleteBracket($tagName)
    {
		$prettyName = substr($tagName, 1);
		return $prettyName;
	}

	private static function getDefaultFieldID()
    {
		// сreate an object of field
		$model_field = new ABIModel_Param();
		$model_field->setField('type', 'integer');
        $model_field->setField('collection', '');
		$model_field->setField('unique', 'true');
		$model_field->setField('default', '');
		$model_field->setField('ai', 'true');
		$model_field->setField('require', 'false');
		$model_field->setField('length', 9);
		$model_field->setField('pattern', '');
		$model_field->setField('bind_db', 'true');

		$model_field->checkError();
		return (object) $model_field->getAllProperty();
	}

	private static function createModelField($field_params)
    {
		// сreate an object of field
		$model_field = new ABIModel_Param();

		foreach ($field_params as $param_name => $param_value) {
			$model_field->setField($param_name, $param_value);
		}

		$model_field->checkError();
		return $model_field;
	}

    private static function getFieldNameByNumber($model, $field_number_need)
    {
        if ($field_number_need > count((array)$model)) {
            $field_name = 'ADD COLUMN';
        } else {
            $field_number = 1;

            foreach ($model as $field_name => $field_params) {
                if ($field_number < $field_number_need) {
                    $field_number++;
                    continue;
                }
                break;
            }
        }

        return $field_name;
    }

    private static function undoActions($data)
    {
        self::undoActionDeleteModel($data['file_pattern_path']);
        self::undoActionRevertModel($data['old_file_pattern_path'], 'xml');

        self::undoActionDeleteModel($data['file_entity_path']);
        self::undoActionRevertModel($data['old_file_entity_path'], 'php');
    }

    private static function undoActionRevertModel($file_path, $file_extension)
    {
        chmod($file_path . '.OLD.' . $file_extension, 0777);
        // TODO: error if rename returned the 'false' value
        rename($file_path . '.OLD.' . $file_extension, $file_path);
    }

    private static function undoActionDeleteModel($file_path)
    {
        chmod($file_path, 0777);
        // TODO: error if unlink returned the 'false' value
        unlink($file_path);
    }

    public static function getPath($config_path)
    {
        $abi_path = stristr(
            __DIR__,
            'abi' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'classes',
            true
        );

        $config_param_arr = explode('/', Settings::getParam($config_path));
        $config_param = implode(DIRECTORY_SEPARATOR, $config_param_arr);
        $path = $abi_path . $config_param;
        return $path;
    }

	public static function getModelPattern($model_name, $driver = false)
    {
		// create an object of field
		$pattern_fields = new Params();

		$arrTags = array ('<name' => 1);
		$arrTags = array_merge($arrTags, $pattern_fields->getFieldsName());

		if ($driver) {
			$xml = file_get_contents(
			    self::getPath('driver_patterns_rf') . Settings::getParam('driver') .
                DIRECTORY_SEPARATOR . $model_name . '.xml'
            );
		} else {
			$xml = file_get_contents(self::getPath('custom_patterns_rf') . $model_name . '.xml');
		}

		if ('' === $xml) {
			EventHandler::error("The model '$model_name' is wrong");
		} elseif (!$xml) {
			EventHandler::error("The model '$model_name' not found");
		}

		$arrXMLTags = self::retrieveTagsFromXML($xml);
		$pattern = array();
		$current_model_field_name = false;

		foreach ($arrXMLTags[0] as $arrMsgTag) {
			$tagName = self::deleteAttrFromTag($arrMsgTag);

			// if a start tag
			if ('/' !== $tagName[1]) {
				// the start tag is waiting end tag
				if (1 === $arrTags[$tagName]) {
					$value = self::getAttrValue($arrMsgTag);
					$model_field = new ABIModel_Param();
					$pattern[$value] = $model_field;
					$current_model_field_name = $value;
				} elseif (0 === $arrTags[$tagName] && $current_model_field_name) {
					$value = self::getAttrValue($arrMsgTag);
					$field_property_name = self::deleteBracket($tagName);
					$pattern[$current_model_field_name]->setField($field_property_name, $value);
				}
			} else {
				$pattern[$current_model_field_name]->checkError();
				$current_model_field_name = false;
			}
		}

		return $pattern;
	}

	public static function getAllDatabaseDrivers()
    {
		$drivers_name_arr = array();
		$path_to_drivers = self::getPath('driver_rf');
		$drivers = array_diff(scandir($path_to_drivers), array ('..', '.'));

		foreach ($drivers as $driver) {
			$driver_name_arr = explode("_", $driver);
			$drivers_name_arr[] = mb_strtolower(array_shift($driver_name_arr));
		}

		return $drivers_name_arr;
	}

	public static function getAllModelPattern()
    {
		$result = array();
		$path_to_model = self::getPath('custom_patterns_rf');
		$files = array_diff(scandir($path_to_model), array ('..', '.'));

		foreach ($files as $file_name) {
			$file_name_arr = explode(".", $file_name);
			$extension = array_pop($file_name_arr);
			$model_name = implode('.', $file_name_arr);

			if ($extension === 'htaccess') {
				continue;
			}

			if ($extension !== 'xml') {
				unlink($path_to_model.$file_name);
				continue;
			}

			$model_param = self::getModelPattern($model_name);

			foreach ($model_param as $model_param_name => $model_param_value) {
				$params = $model_param_value->getAllProperty();
				$model_param[$model_param_name] = (object) $params;
			}

			$result[$model_name] = (object) $model_param;
		}

		return $result;
	}

	public static function setModelPattern($model)
    {
		if (!is_object($model)) {
		    EventHandler::error("The model pattern must be object");
		}

		foreach ($model as $model_name => $model_pattern) {
			if (!is_object($model_pattern)) {
			    EventHandler::error("The model pattern must be object");
			}

            Types::isValidModelName($model_name);

			$file_pattern_path = self::getPath('custom_patterns_rf') . $model_name . '.xml';
			$xml = '';
			$count_bind_db_fields = 0;
			$count_ai_fields = 0;

			// set required field 'ID'
			$model_id_name = $model_name . '_id';

			if (property_exists($model->$model_name, $model_id_name)) {
				if (!in_array(Settings::getParam('driver'), self::getAllDatabaseDrivers())) {
					EventHandler::error('The database driver ' . Settings::getParam('driver') . ' unavailable');
				}
				$model_bind_db = true;
				$model->$model_name->$model_id_name = self::getDefaultFieldID();
			} else {
				$model_bind_db = false;
			}

			foreach ($model_pattern as $field_key => $field_params) {
                Types::isValidFieldName($field_key);

				unset ($model->$model_name->$field_key->abi_index_param);
				// TODO: CREATE FUNCTION FOR DELETE INDEX FIELD + AND IN THE CREATE METHOD

				$model_field = self::createModelField($field_params);

				if (!$model_bind_db && 'true' === $model_field->getField('bind_db')) {
					EventHandler::error("Cannot create this model. There are fields set as 'bind to database', but the mandatory default '$model_id_name' field is not found");
				} elseif ($model_bind_db) {
					if ('true' === $model_field->getField('bind_db')) {
					    $count_bind_db_fields++;
					}
					if ('true' === $model_field->getField('ai')) {
					    $count_ai_fields++;
					}
					if ($count_ai_fields > 1) {
						EventHandler::validationError("The 'auto increment' parameter must not exceed 1 field");
					}
				}

				$xml .= $model_field->getXMLParams($field_key) . "\n";

				$standard = Validator::getStandard($model_field->getField('type'));
                Validator::checkModelFieldStructure($model_field, $standard);

				$model->$model_name->$field_key = $model_field;
			}

			if ($model_bind_db && $count_bind_db_fields < 2) {
				EventHandler::error("The mandatory default '$model_id_name' field is found, but no one field else set as 'bind to database'");
			}

			if ($new_xml_file = fopen($file_pattern_path, 'w')) {
				fwrite($new_xml_file, $xml);
				fclose($new_xml_file);
				chmod($file_pattern_path, 0777);

				if ($model_bind_db) {
					try {
						$db_inst = new Database();
					} catch (\Exception $e) {
                        chmod(self::getPath('custom_patterns_rf') . $model_name . '.xml', 0777);
						// TODO: fatal error if unlink returned the 'false' value
						unlink(self::getPath('custom_patterns_rf') . $model_name . '.xml');
                        EventHandler::error(
                            "The model '$model_name' was not created. Database connection error. " .
                            $e->getMessage()
                        );
					}

					if (!$db_inst->createMPTable($model)) {
						chmod(self::getPath('custom_patterns_rf') . $model_name . '.xml', 0777);
						// TODO: fatal error if unlink returned the 'false' value
						unlink(self::getPath('custom_patterns_rf') . $model_name . '.xml');
						EventHandler::error(
						    "The model was not created. Cannot create the model '$model_name' to database. " .
                            $db_inst->getLastError()
                        );
					}
				}

			} else {
                EventHandler::error("Permission denied. Cannot reading or creating file '$file_pattern_path'");
			}

            $entity_content = file_get_contents(
                Parser::getPath('entity_standard_rf') . Settings::getParam('entity_strd_name'),
                FILE_USE_INCLUDE_PATH
            );

            $entity_content = str_replace('{Entity_Standard_Name}', ucfirst($model_name), $entity_content);
            $new_file_path = Parser::getPath('entity_standard_rf') . ucfirst($model_name) . '.php';

            if ($new_entity_file = fopen($new_file_path, 'w')) {
                fwrite($new_entity_file, $entity_content);
                fclose($new_entity_file);
                chmod($new_file_path, 0777);
            } else {
                self::deleteModelPattern($model_name, $model_bind_db);
                EventHandler::error("Permission denied. Cannot reading or creating file '$new_file_path'");
            }
		}
	}

	public static function updateModelPattern($model)
    {
		if (!is_object($model)) {
		    EventHandler::error("The model pattern must be object");
		}

		if ((!property_exists($model, 'old_model_name')) || !is_string($model->old_model_name)) {
			EventHandler::error("The model pattern must have the 'old_model_name' parameter of 'String' type");
		}

		$old_model_name = $model->old_model_name;
		unset ($model->old_model_name);

		$old_model = (object) array ($old_model_name => (object) self::getModelPattern($old_model_name));
		$map = array();

		foreach ($model as $model_name => $model_pattern) {
			if (!is_object($model_pattern)) {
			    EventHandler::error("The model pattern must be object");
			}

            Types::isValidModelName($model_name, true);

			$file_pattern_path = self::getPath('custom_patterns_rf') . $model_name . '.xml';
            $file_entity_path = self::getPath('entity_standard_rf') . ucfirst($model_name) . '.php';
			$xml = '';
			$count_bind_db_fields = 0;
			$count_ai_fields = 0;

			// set required field 'ID'
			$model_id_name = $model_name . '_id';

			if (property_exists($model->$model_name, $model_id_name)) {
				if (!in_array(Settings::getParam('driver'), self::getAllDatabaseDrivers())) {
					EventHandler::error('The database driver ' . Settings::getParam('driver') . ' unavailable');
				}

				$model_bind_db = true;

				$old_id_field_name = self::getFieldNameByNumber(
				    $old_model->$old_model_name,
					$model->$model_name->$model_id_name->abi_index_param
                );

				$model->$model_name->$model_id_name = self::getDefaultFieldID();
			} else {
				$model_bind_db = false;
			}

			$index_new_model_field = 1;
			$max_index_model_field = 0;

			foreach ($model_pattern as $field_key => $field_params) {
                Types::isValidFieldName($field_key);

				if ($model_bind_db) {
					if ($field_key === $model_id_name) {
						$map[$model_id_name] = $old_id_field_name;
						$index_new_model_field++;
					} else {
						$index_new_field = $field_params->abi_index_param;

						if ($index_new_field > $max_index_model_field) {
							$max_index_model_field = $index_new_field;
						}

						// add old field that need to delete
						while ($index_new_model_field < $index_new_field) {
							$old_field_name = self::getFieldNameByNumber(
							    $old_model->$old_model_name,
								$index_new_model_field
                            );
							$map[$old_field_name] = 'DELETE COLUMN';
							$index_new_model_field++;
						}

						$old_field_name = self::getFieldNameByNumber(
						    $old_model->$old_model_name,
							$field_params->abi_index_param
                        );
						$map[$field_key] = $old_field_name;
						$index_new_model_field++;
					}
				}

				unset ($model->$model_name->$field_key->abi_index_param);
				// TODO: CREATE FUNCTION FOR DELETE INDEX FIELD + AND IN THE CREATE METHOD

				$model_field = self::createModelField($field_params);

				if (!$model_bind_db && 'true' === $model_field->getField('bind_db')) {
					EventHandler::error("Cannot create this model. There are fields set as 'bind to database', but the mandatory default '$model_id_name' field is not found");
				} elseif ($model_bind_db) {
					if ('true' === $model_field->getField('bind_db')) {
					    $count_bind_db_fields++;
					}
					if ('true' === $model_field->getField('ai')) {
					    $count_ai_fields++;
					}
					if ($count_ai_fields > 1) {
						EventHandler::validationError("The 'auto increment' parameter must not exceed 1 field");
					}
				}

				$xml .= $model_field->getXMLParams($field_key) . "\n";

				$standard = Validator::getStandard($model_field->getField('type'));
                Validator::checkModelFieldStructure($model_field, $standard);

				$model->$model_name->$field_key = $model_field;
			}

			// add old field that need to delete
			$num_old_model_field = 0;
			foreach ($old_model->$old_model_name as $old_model_field_name => $old_model_field) {
				if (($num_old_model_field >= $max_index_model_field) && 'true' === $old_model_field->getField('bind_db')) {
					$map[$old_model_field_name] = 'DELETE COLUMN';
				}
				$num_old_model_field++;
			}

			if ($model_bind_db && $count_bind_db_fields < 2) {
				EventHandler::error("The mandatory default '$model_id_name' field is found, but no one field else set as 'bind to database'");
			}

			$old_file_pattern_path = self::getPath('custom_patterns_rf') . $old_model_name . '.xml';
			chmod($old_file_pattern_path, 0777);

            $old_file_entity_path = self::getPath('entity_standard_rf') . ucfirst($old_model_name) . '.php';
            chmod($old_file_entity_path, 0777);

            $arr_files_info = array (
                'file_pattern_path'     => $file_pattern_path,
                'old_file_pattern_path' => $old_file_pattern_path,
                'file_entity_path'      => $file_entity_path,
                'old_file_entity_path'  => $old_file_entity_path
            );

			if (rename($old_file_pattern_path, $old_file_pattern_path . '.OLD.xml')) {
				if ($new_xml_file = fopen($file_pattern_path, 'w')) {
				    // update model pattern
					fwrite($new_xml_file, $xml);
					fclose($new_xml_file);
					chmod($file_pattern_path, 0777);

					if ($model_name !== $old_model_name) {
                        if (rename($old_file_entity_path, $old_file_entity_path . '.OLD.php')) {
                            if ($new_entity_file = fopen($file_entity_path, 'w')) {
                                // update model entity
                                $entity_content = file_get_contents(
                                    Parser::getPath('entity_standard_rf') . Settings::getParam('entity_strd_name'),
                                    FILE_USE_INCLUDE_PATH
                                );

                                $entity_content = str_replace(
                                    '{Entity_Standard_Name}',
                                    ucfirst($model_name),
                                    $entity_content
                                );

                                fwrite($new_entity_file, $entity_content);
                                fclose($new_entity_file);
                                chmod($file_entity_path, 0777);
                            } else {
                                self::undoActions($arr_files_info);
                                EventHandler::error("Permission denied. Cannot reading or creating file '$file_entity_path'");
                            }
                        } else {
                            EventHandler::error("Permission denied. Cannot reading or creating file '$old_file_entity_path'");
                        }
                    }

					if ($model_bind_db || in_array('DELETE COLUMN', $map)) {
						try {
							$db_inst = new Database();
							foreach ($old_model as $old_model_name => $old_model_fields) {
								foreach ($old_model_fields as $old_model_values) {
									if ($old_model_values->getField('length')) {
										$old_model_values->setField('length', $old_model_values->getField('length') * 1);
									}
									$standard = Validator::getStandard($old_model_values->getField('type'));
                                    Validator::checkModelFieldStructure($old_model_values, $standard, true);
								}
							}

							$is_updated_table = $db_inst->updateMPTable($model, $old_model, $map);
						} catch (\Exception $e) {
                            self::undoActions($arr_files_info);
							EventHandler::error("The model '$model_name' was not updated. " . $e->getMessage());
						}

						if (!$is_updated_table) {
                            self::undoActions($arr_files_info);
							EventHandler::error("Cannot update the model in database. " . $db_inst->getLastError());
						}
					}

					self::undoActionDeleteModel($old_file_pattern_path . '.OLD.xml');
                    self::undoActionDeleteModel($old_file_entity_path . '.OLD.php');
				} else {
                    self::undoActions($arr_files_info);
					EventHandler::error("Permission denied. Cannot reading or creating file '$file_pattern_path'");
				}
			} else {
				EventHandler::error("Permission denied. Cannot reading or creating file '$old_file_pattern_path'");
			}
		}
	}

	public static function deleteModelPattern($model_name, $model_bind_db = false)
    {
        if (true === $model_bind_db) {
            $db_inst = new Database();
            $db_inst->deleteMPTable($model_name);
        }

        chmod(self::getPath('custom_patterns_rf') . $model_name . '.xml', 0777);
		// TODO: fatal error if unlink returned the 'false' value
		unlink(self::getPath('custom_patterns_rf').$model_name.'.xml');

        chmod(self::getPath('entity_standard_rf') . ucfirst($model_name) . '.php', 0777);
        // TODO: fatal error if unlink returned the 'false' value
        unlink(self::getPath('entity_standard_rf') . ucfirst($model_name) . '.php');
	}

	public static function validateFrontendModelField($field)
    {
		if (!$field) {
            EventHandler::error('Request body is empty');
		}

		foreach ($field as $field_name => $field_params) {
			if (!is_object($field_params)) {
			    EventHandler::error('The field pattern must be object');
			}

            Types::isValidFieldName($field_name);

			$model_field = self::createModelField($field_params);
			$standard = Validator::getStandard($model_field->getField('type'));
            Validator::checkModelFieldStructure($model_field, $standard);
		}
	}
}
