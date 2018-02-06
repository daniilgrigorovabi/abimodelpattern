<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

/**
 * An exception class
 */
class ABIException extends \Exception {
	public function __construct($msg = '', $code = 0) {
		if(!is_string($msg)) {
            EventHandler::error("The error message must be of type 'String'. ".ucfirst(gettype($msg)).' to string conversion in '.__FILE__);
		}
		parent::__construct($msg, $code);
	}
}
