<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

class ServerCollection
{
    private $data = [];

    public function __construct($items)
    {
        foreach ($items as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }
}
