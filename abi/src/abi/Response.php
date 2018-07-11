<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

class Response
{
    const SUCCESS = 'success';
    const ERROR   = 'error';
	const NOTIFICATION = 'notification';

    private $status;
    private $data;
    private $message;

    public function __construct($status)
    {
        if (self::ERROR === $status || self::SUCCESS === $status || self::NOTIFICATION === $status) {
            $this->status = $status;
        } else {
            $this->status = self::ERROR;
        }
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setMessage($msg)
    {
        if (!is_string($msg)) {
            EventHandler::error("The response value 'message' must be of type 'String'");
        }
        
        if (self::ERROR === $this->status || self::NOTIFICATION === $this->status) {
            $this->message = $msg;
        } else {
            EventHandler::error("The response value 'message' can be used only for 'error' type");
        }
    }

    public function getJSONResponse()
    {
        if ((self::ERROR === $this->status || self::NOTIFICATION === $this->status) && !$this->message) {
            EventHandler::error("The response is incorrect");
        } elseif (self::SUCCESS === $this->status && !$this->data) {
        	$this->data = array ('ABI_success_message' => 'Empty response');
        }

        foreach ($this as $key => $value) {
            if (!$value) {
                unset ($this->$key);
            }
        }

        return json_encode(get_object_vars($this));
    }
}
