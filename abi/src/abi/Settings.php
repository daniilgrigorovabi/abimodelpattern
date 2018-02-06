<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

use ABI\classes\Parser;

class Settings {
	private static $instance = null;
	private static $settings = null;

	private function __clone() {}
	private function __construct() {}

    private static function _setSettings($filePath) {
        if(!self::$settings = parse_ini_file($filePath)) {
			http_response_code (503);
            exit(json_encode(array('message' => "Opening the '$filePath' file for writing or reading failed")));
        }
		chmod($filePath, 0777);
    }

	public static function getInstance() {
		if (null === self::$instance) {
		    $path_arr = explode(DIRECTORY_SEPARATOR, __DIR__);
            array_splice($path_arr, count($path_arr)-2);
            $path = implode(DIRECTORY_SEPARATOR, $path_arr);

			self::_setSettings($path.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'abi.ini');
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function getParam($param) {
    	if(!array_key_exists($param, self::$settings)) {
			EventHandler::error("The configuration parameter '$param' was not found in the ".__FILE__);
		}

		return self::$settings[$param];
	}

	public static function update($section, $new_fields_arr) {
		$config_data = parse_ini_file(Parser::getPath('config_rf'), true);

		foreach ($new_fields_arr as $param_name => $param_value) {
			if(array_key_exists($param_name, $config_data[$section])) {
				$config_data[$section][$param_name] = $param_value;
			}
		}

		$new_content = '';

		foreach ($config_data as $section => $section_content) {
			$section_content = array_map(function($value, $key) {
				return "$key = '$value'";
			}, array_values($section_content), array_keys($section_content));
			$section_content = implode("\n", $section_content);
			$new_content .= "[$section]\n$section_content\n\n";
		}

		$new_content = substr($new_content,0,-2);

		chmod(Parser::getPath('config_rf'), 0777);

		if(!file_put_contents(Parser::getPath('config_rf'), $new_content)) {
			EventHandler::error('Cannot update the parameters of settings');
		}
	}
}
