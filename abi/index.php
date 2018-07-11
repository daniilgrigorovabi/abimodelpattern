<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

require_once ('vendor/autoload.php');

try {
    \ABI\Settings::getInstance();

    $router = new \ABI\Router();

    // admin
    $router->post('/admin/login', 'adminAuthentication', false);
    $router->post('/admin/check', 'adminCheckAccess', false);
    $router->post('/admin/complete', 'adminUpdatePassword');

    // app
    $router->get('/app/status', 'getApplicationStatus');
    $router->get('/app/assets', 'getAvailableTypes');

    // database
    $router->get('/db/params', 'getDBParams');
    $router->post('/db/params', 'updateDBSettings');
    $router->post('/db/check', 'checkDBSettings');

    // logger
    $router->get('/logger/params', 'getLoggerParams');
    $router->post('/logger/params', 'updateLoggerSettings');
    $router->get('/logger/messages', 'getLastLoggerMessages');

    // model
    $router->get('/model/list', 'getModelList');
    $router->post('/model/add', 'setModel');
    $router->post('/model/update', 'updateModel');
    $router->post('/model/delete', 'deleteModel');
    $router->post('/model/validate', 'validateModelField');

    $router->exec();
} catch (\ABI\ABIException $error) {
	if (422 === $error->getCode()) {
		$response_inst = new \ABI\Response('notification');
	} else {
		$response_inst = new \ABI\Response('error');
	}

    $response_inst->setMessage($error->getMessage());
    ob_end_clean();
    echo $response_inst->getJSONResponse();
} catch (Throwable $error) {
    $response_inst = new \ABI\Response('error');
    $response_inst->setMessage($error->getMessage());
    ob_end_clean();
    echo $response_inst->getJSONResponse();
}
