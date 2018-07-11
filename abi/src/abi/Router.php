<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

class Router
{
    private $routes = [];

    private $request;

    public function __construct()
    {
        ob_start();
        $this->request = new Request();
    }

    public function addRule($rule, $function)
    {
        $this->routes[$function] = $rule;
    }

    public function get($rule, $function, $access = true)
    {
        $this->routes['GET'][] = array(
            'function' => $function,
            'rule'     => $rule,
            'access'   => $access
        );
    }

    public function post($rule, $function, $access = true)
    {
        $this->routes['POST'][] = array(
            'function' => $function,
            'rule'     => $rule,
            'access'   => $access
        );
    }

    public function exec()
    {
        foreach ($this->routes[$this->request->getMethod()] as $route) {
            if ($route['rule'] === $this->request->getRoute()) {
                $data = $this->request->getParsedBody();
                $response_inst = new Response('success');

                if (true === $route['access']) {
                    $admin_inst = new \ABI\Admin();

                    try {
                        $result = $admin_inst->validateToken($data->access_token);
                    } catch (\Exception $e) {
                        EventHandler::error("Access deny");
                    }

                    unset ($data->access_token);

                    if (
                        false === $result['status'] &&
                        ('adminUpdatePassword' !== $route['function'] ||
                        $result['status_msg'] !== $admin_inst::STATUS_MSG_PENDING)
                    ) {
                        $response_inst->setData($result);
                        break;
                    }
                }

                $function_name = $route['function'];
                $result = $function_name($data);
                $response_inst->setData($result);
                break;
            }
        }

        if (!isset($result)) {
            $response_inst = new Response('error');
            $response_inst->setMessage('Call to undefined route');
        }

        ob_end_clean();
        echo $response_inst->getJSONResponse();
    }
}
