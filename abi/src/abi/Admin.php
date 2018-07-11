<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

use Firebase\JWT\JWT;

class Admin
{
    const STATUS_MSG_TRUE = 'Access allow';
    const STATUS_MSG_FALSE = 'Access deny';
    const STATUS_MSG_PENDING = 'Need to update password';

    private $private_key = 'RJ8iapbY6wtCqwBTrTzLx19ruBgamURkXFoupbAz_CO_PUwlpLTOWqQk4FM6ghUC2_SwLsE2YsVMU36TqqNAoQ';
    private $require_data = [];

    public function __construct()
    {
        $this->require_data = array (
            'expiration_time' => time() + Settings::getParam('access_token_ttl')
        );
    }

    private function isValidAdmin($data) {
        if (
            password_verify($data->passwd, Settings::getParam('admin_passwd')) &&
            $data->login === Settings::getParam('admin_login')
        ) {
            $is_valid = true;
        }
        return isset($is_valid) ? self::STATUS_MSG_TRUE : self::STATUS_MSG_FALSE;
    }

    private function parseToken($token)
    {
        $decoded = JWT::decode($token, $this->private_key, array ('HS256'));
        return $decoded;
    }

    private function getAdminStatus($data, $ckeck_admin = true)
    {
        if (time() >= $data->expiration_time) {
            $current_status = self::STATUS_MSG_FALSE;
        } else {
            if ($ckeck_admin) {
                $current_status = $this->isValidAdmin($data);
            } else {
                $current_status = self::STATUS_MSG_TRUE;
            }

            if (self::STATUS_MSG_TRUE === $current_status && !$this->validatePassword($data->passwd)) {
                $current_status = self::STATUS_MSG_PENDING;
            }
        }
        return $current_status;
    }

    private function checkAdminStatus($status_msg) {
        if (self::STATUS_MSG_TRUE === $status_msg) {
            $status = true;
        } else {
            $status = false;
        }
        return $status;
    }

    private function addRequiredFields($data) {
        foreach ($this->require_data as $field_key => $field_value) {
            $data->$field_key = $field_value;
        }
        return $data;
    }

    public function validatePassword($passwd)
    {
        $isValid = true;

        $uppercase = preg_match('@[A-Z]@', $passwd);
        $lowercase = preg_match('@[a-z]@', $passwd);
        $number    = preg_match('@[0-9]@', $passwd);

        if (!$uppercase || !$lowercase || !$number || strlen($passwd) < 8) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * IMPORTANT:
     * You must specify supported algorithms for your application. See
     * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
     * for a list of spec-compliant algorithms.
     */
    public function generateToken($data, $ckeck_admin = true)
    {
        $token_data = $this->addRequiredFields((object) []);
        $token_data->login = $data->login;
        $token_data->passwd = $data->passwd;

        $status_msg = $this->getAdminStatus($token_data, $ckeck_admin);
        $status = $this->checkAdminStatus($status_msg);

        $result = array (
            'status' => $status,
            'status_msg' => $status_msg
        );

        if ($status_msg !== self::STATUS_MSG_FALSE) {
            $token = JWT::encode($token_data, $this->private_key);
            $result['access_token'] = $token;
        }

        return $result;
    }

    public function validateToken($token)
    {
        $data = $this->parseToken($token);
        $status_msg = $this->getAdminStatus($data);
        $status = $this->checkAdminStatus($status_msg);

        $result = array (
            'status' => $status,
            'status_msg' => $status_msg
        );

        return $result;
    }
}
