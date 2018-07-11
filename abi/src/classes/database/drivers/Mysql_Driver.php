<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\database\drivers;

use ABI\classes\database\IDatabase_Driver;
use ABI\classes\Types;
use ABI\EventHandler;
use ABI\Settings;

class Mysql_Driver implements IDatabase_Driver
{
	private $connection;
	private $last_error;

	private function getDescribeTable($table_name)
    {
		// get information about table columns
		$sth = $this->connection->prepare('DESCRIBE ' . '`' . Settings::getParam('prefix') . $table_name . '`');
		$sth->execute();
		$model_columns_arr = $sth->fetchAll(\PDO::FETCH_OBJ);
		return $model_columns_arr;
	}

    private function getColumnDefinition($column_params)
    {
        $column_definition = $column_params->getField('type');

        if ($column_params->getField('length')) {
            $column_definition .= '(' . $column_params->getField('length') . ')';
        }

        if ('true' === $column_params->getField('ai')) {
            $column_definition .= ' AUTO_INCREMENT';
        }

        if ($column_params->getField('default')) {
            if (
                Types::CURRENT_TIMESTAMP === $column_params->getField('default') &&
                'date' === $column_params->getField('type')
            ) {
                goto without_default;
            }

            if (
                is_string($column_params->getField('default')) &&
                Types::CURRENT_TIMESTAMP !== $column_params->getField('default')
            ) {
                $default_value = "'" . $column_params->getField('default') . "'";
            } else {
                $default_value = $column_params->getField('default');
            }

            $column_definition .= ' DEFAULT ' . $default_value;
            without_default:
        }

        $column_definition .= ' NOT NULL';
        $column_definition .= ' COLLATE utf8_general_ci';
        return $column_definition;
    }

	public function setConnection($db)
    {
		$this->connection = $db;
	}

	public function getLastError()
    {
		return $this->last_error;
	}

	public function createMPDatabase($db_name)
    {
		$sql = "CREATE DATABASE IF NOT EXISTS $db_name;";
		$sth = $this->connection->prepare($sql);
		if (!$sth->execute()) {
		    EventHandler::error("Trouble with database name");
		}
	}

	public function useMPDatabase($db_name)
    {
		$this->connection->exec("USE $db_name");
	}

	public function createMPTable($model)
    {
		$unique_columns = array();
		$sql_columns_params = array();
        $execute_result = false;

		foreach ($model as $model_name => $model_pattern) {
			// the table of model exists
			if ($this->getDescribeTable($model_name)) {
			    EventHandler::error("The table with name '$model_name' already exists");
			}

			$sql = 'CREATE TABLE ' . '`' . Settings::getParam('prefix') . $model_name . '`' . ' (';

			foreach ($model_pattern as $column_name => $column_params) {
				if ('true' === $column_params->getField('bind_db')) {
                    $sql_columns_params[$column_name] .= "$column_name " . $this->getColumnDefinition($column_params);

					if ('true' === $column_params->getField('unique')) {
						$unique_columns[] = $column_name;
					}
				}
			}

			if ($sql_columns_params) {
			    $sql .= implode(', ', $sql_columns_params);
			}

			if ($unique_columns) {
				foreach ($unique_columns as $unique_column) {
					$sql .= ", UNIQUE KEY $unique_column ($unique_column)";
				}
			}

			$sql .= ', PRIMARY KEY (' . $model_name . '_id)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';

			$sth = $this->connection->prepare($sql);

			if (!$execute_result = $sth->execute()) {
				$error_db_info = $sth->errorInfo();
				$this->last_error = $error_db_info[2];
			}
		}

		return $execute_result;
	}

	public function updateMPTable($model, $old_model, $map)
    {
		$sql_query_array_num = 0;
		$sql = '';
		$sql_query_array = array();
		$execute_result = true;

        $old_model_name = key($old_model);
        $old_model_pattern = $old_model->$old_model_name;

		foreach ($model as $model_name => $model_pattern) {
			$table_desc = $this->getDescribeTable($old_model_name);

			if ($table_desc) {
				// if need to delete table
				if (false == array_filter($map, function ($all_delete) {
				        return $all_delete !== 'DELETE COLUMN';
				    })
                ) {
					$num_old_model_pattern_bind_field = 0;
					foreach ($old_model_pattern as $old_model_pattern_field) {
						if ('true' === $old_model_pattern_field->getField('bind_db')) {
							$num_old_model_pattern_bind_field++;
						}
					}

					if ($num_old_model_pattern_bind_field === count($map)) {
						$this->deleteMPTable($old_model_name);
                        return $execute_result;
					}
				}

				$sql = 'ALTER TABLE ' . '`' . Settings::getParam('prefix') . $old_model_name . '`';
			} else {
				// if need to create table
				$execute_result = $this->createMPTable($model);
                return $execute_result;
			}

			// update table name
			if ($model_name !== $old_model_name) {
				$sql_query_array[$sql_query_array_num] .= ' RENAME ' . '`' . Settings::getParam('prefix') .
                    $model_name . '`' . ' ';
			}

			foreach ($model_pattern as $column_name => $column_params) {
				if ($model_name . '_id' === $column_name) {
					foreach ($table_desc as $table_column) {
						if ($table_column->Field === $column_name) {
							continue 2;
						}
					}

					$sql_query_array_num++;
					$sql_query_array[$sql_query_array_num] .= "DROP PRIMARY KEY, ADD CONSTRAINT $column_name PRIMARY KEY ($column_name)";
				}

				$is_change_column_name = false;
				$sql_query_array_num++;

				if ('true' === $column_params->getField('bind_db')) {
					$old_model_field_name = $map[$column_name];

					if ('ADD COLUMN' === $old_model_field_name) {
					    $sql_query_array[$sql_query_array_num] .= " ADD $column_name " . $this->getColumnDefinition($column_params);

						if ('true' === $column_params->getField('unique')) {
							$sql_query_array_num++;
							$sql_query_array[$sql_query_array_num] .= "ADD INDEX $column_name ($column_name) ";
						}

						unset ($map[$column_name]);
					} else {
						$old_column_params = $old_model->$old_model_name->$old_model_field_name;

						if ($old_column_params == $column_params && $old_model_field_name === $column_name) {
							unset ($map[$column_name]);
							continue;
						}

						if ($old_model_field_name === $column_name) {
							// MODIFY without rename
							$sql_query_array[$sql_query_array_num] .= " MODIFY $old_model_field_name ";
						} else {
							// CHANGE with rename from '$old_model_field_name' to '$column_name'
							$sql_query_array[$sql_query_array_num] .= " CHANGE $old_model_field_name $column_name ";
							$is_change_column_name = true;
						}

						$sql_query_array[$sql_query_array_num] .= $this->getColumnDefinition($column_params);
						$sql_query_array_num++;

						if ($column_params->getField('unique') !== $old_column_params->getField('unique')) {
							// from 'unique' to 'not unique'
							if (
							    'false' === $column_params->getField('unique') &&
                                'true' === $old_column_params->getField('unique')
                            ) {
								$sql_query_array[$sql_query_array_num] .= "DROP INDEX $map[$column_name]";
							}
							// from 'not unique' to 'unique'
							elseif (
							    'true' === $column_params->getField('unique') &&
                                'false' === $old_column_params->getField('unique')
                            ) {
								$sql_query_array[$sql_query_array_num] .= "ADD INDEX $column_name ($column_name)";
							}
						}
						// from 'unique' to 'unique' with rename column name
						elseif (
						    $is_change_column_name &&
                            'true' === $column_params->getField('unique') &&
                            'true' === $old_column_params->getField('unique')
                        ) {
							$sql_query_array[$sql_query_array_num] .= "DROP INDEX $map[$column_name]";
							$sql_query_array_num++;
							$sql_query_array[$sql_query_array_num] .= "ADD INDEX $column_name ($column_name)";
						}

						unset ($map[$column_name]);
					}
				}
			}

			foreach ($map as $field_name => $field_value) {
				if ($field_value === 'DELETE COLUMN') {
					$sql_query_array_num++;
					$sql_query_array[$sql_query_array_num] .= " DROP COLUMN $field_name";
				}
			}
		}

		if ($sql_query_array) {
			$sql .= implode(' , ', $sql_query_array) . ';';
			$sth = $this->connection->prepare($sql);

			if (!$execute_result = $sth->execute()) {
				$error_db_info = $sth->errorInfo();
				$this->last_error = $error_db_info[2];
			}
		}

		return $execute_result;
	}

	public function deleteMPTable($model_name)
    {
		if ($this->getDescribeTable($model_name)) {
			$sql = 'DROP TABLE ' . '`' . Settings::getParam('prefix') . $model_name . '`';
			$sth = $this->connection->prepare($sql);

            if (!$sth->execute()) {
                EventHandler::error("The table '$model_name' was not deleted from database");
            }
		}
	}

	public function exportMPData($model_names)
    {
		// TODO
	}

	public function importMPData($model_names)
    {
        // TODO
	}
}
