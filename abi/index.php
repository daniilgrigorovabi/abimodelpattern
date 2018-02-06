<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

require_once ('vendor/autoload.php');
require_once ('functions.php');

try {
    \ABI\Settings::getInstance();
    ob_start();
    $result = null;

    foreach($_POST as $function_name => $parameters) {
        if (function_exists($function_name)) {
            $result = $function_name($parameters);
            $response_inst = new \ABI\Response('success');
            $response_inst->setData($result);
        } else {
            $response_inst = new \ABI\Response('error');
            $response_inst->setMessage('Call to undefined method');
        }
        break;
    }

    if(!$_POST) {
        $response_inst = new \ABI\Response('error');
        $response_inst->setMessage('Method not allowed');
    }

    ob_end_clean();
    echo $response_inst->getJSONResponse();
} catch (\ABI\ABIException $error) {
    ob_end_clean();

	if(422 === $error->getCode()) {
		$response_inst = new \ABI\Response('notification');
	} else {
		$response_inst = new \ABI\Response('error');
	}

    $response_inst->setMessage($error->getMessage());
    echo $response_inst->getJSONResponse();
} catch (Throwable $error) {
    ob_end_clean();
    $response_inst = new \ABI\Response('error');
    $response_inst->setMessage($error->getMessage());
    echo $response_inst->getJSONResponse();
}
