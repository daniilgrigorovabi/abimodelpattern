<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

class Request
{
    private $method;

    private $uri;

    private $body;

    public function __construct()
    {
        $server_collection = new ServerCollection($_SERVER);

        $this->method = $server_collection->get('REQUEST_METHOD');

        $uri = parse_url($server_collection->get('REQUEST_URI'), PHP_URL_PATH);
        $path_to_route_file = str_replace('/index.php', '', $server_collection->get('SCRIPT_NAME'));
        $uri = substr_replace($uri, '', 0, strlen($path_to_route_file));
        $this->uri = $uri;
        $this->body = file_get_contents('php://input');

        parse_str($server_collection->get('QUERY_STRING'), $query_params);

        if (array_key_exists('access_token', $query_params)) {
            $body_arr = json_decode($this->body);
            $body_arr['access_token'] = $query_params['access_token'];
            $this->body = json_encode($body_arr);
        }
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRoute()
    {
        return $this->uri;
    }

    public function getParsedBody()
    {
        return json_decode($this->body);
    }
}
