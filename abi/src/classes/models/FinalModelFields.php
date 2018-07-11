<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models;

class FinalModelFields
{
	public function setField($property, $value)
    {
		$this->$property = $value;
	}

	public function getField($property)
    {
		return $this->$property;
	}
}
