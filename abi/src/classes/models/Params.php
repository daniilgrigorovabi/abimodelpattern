<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\models;

class Params {
	protected $type    = 'ABI_Field_Error';
	protected $unique  = 'ABI_Field_Error';
	protected $default = 'ABI_Field_Error';
	protected $ai 	   = 'ABI_Field_Error';
	protected $require = 'ABI_Field_Error';
	protected $length  = 'ABI_Field_Error';
	protected $pattern = 'ABI_Field_Error';
	protected $bind_db = 'ABI_Field_Error';

	public function getFieldsName() {
		$fields = array();

		foreach ($this as $field_name => $field_value) {
			$fields['<'.$field_name] = 0;
		}

		return $fields;
	}
}
