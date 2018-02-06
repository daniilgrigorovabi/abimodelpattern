<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models;

use ABI\EventHandler;

class ABIModel_Param extends Params {

	public function setField($field_name, $value){
		if(!property_exists($this, $field_name)) {
            EventHandler::error("Impossible to establish. The field '$field_name' is not on the list of available fields");
		}

		$this->$field_name = $value;
	}

	public function getField($field_name) {
		if(!property_exists($this, $field_name)) {
            EventHandler::error("The field '$field_name' not found on the list of available fields");
		}

		return $this->$field_name;
	}

	public function getXMLParams($name) {
		$param = "<name value=\"$name\">";

		foreach ($this as $param_name => $param_value) {
			$param .= "<$param_name value=\"$param_value\" />";
		}

		$param .= '</name>';

		return $param;
	}

	public function getAllProperty() {
		$params = array();

		foreach ($this as $param_name => $param_value) {
			$params[$param_name] = $param_value;
		}

		return $params;
	}

	public function checkError() {
		foreach ($this as $param_name => $param_value) {
			if('ABI_Field_Error' === $param_value) {
                EventHandler::error("The field '$param_name' has not been used");
			}
		}
	}

}