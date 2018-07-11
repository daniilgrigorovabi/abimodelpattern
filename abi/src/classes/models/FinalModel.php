<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models;

use ABI\classes\Validator;
use ABI\EventHandler;

class FinalModel
{
	private $abi_model_pattern;
	private $fields;

	public function __construct($pattern)
    {
		$this->abi_model_pattern = $pattern;

		if (!$this->fields instanceof FinalModelFields) {
			$this->fields = new FinalModelFields();
		}
	}

	public function __set($property, $value)
    {
		if (!isset($this->abi_model_pattern[$property])) {
			EventHandler::error("Cannot set the nonexistent field '$property' in this model");
		}

		$value = Validator::checkPatternField($value, $this->abi_model_pattern[$property]);
		$this->fields->setField($property, $value);
	}

	public function __get($property)
    {
		return $this->fields->getField($property);
	}

	public function isEmpty() {
        $isEmptyEntity = empty((array) $this->fields) > 0 ? true : false;
        return $isEmptyEntity;
    }
}
