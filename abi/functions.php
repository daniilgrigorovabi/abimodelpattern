<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

/**
 * @param $parameters
 * @return array
 */
function adminAuthentication($parameters)
{
    $admin_inst = new \ABI\Admin();
    $result = $admin_inst->generateToken($parameters);
    return $result;
}

/**
 * @param $parameters
 * @return array
 */
function adminCheckAccess($parameters)
{
    $admin_inst = new \ABI\Admin();

    try {
        $result = $admin_inst->validateToken($parameters->access_token);
    } catch (\Exception $e) {
        \ABI\EventHandler::error("Access deny");
    }

    $result = array ('access' => $result['status']);
    return $result;
}

/**
 * @param $parameters
 * @return array
 */
function adminUpdatePassword($parameters)
{
    $admin_inst = new \ABI\Admin();

    if (!$admin_inst->validatePassword($parameters->passwd)) {
        \ABI\EventHandler::error("The password is not valid");
    }

    \ABI\Settings::update(
        'admin_access',
        ['admin_passwd' => password_hash($parameters->passwd, PASSWORD_DEFAULT)]
    );

    $parameters->login = \ABI\Settings::getParam('admin_login');
    $result = $admin_inst->generateToken($parameters, false);

    $result = array ('access_token' => $result['access_token']);
    return $result;
}

/**
 * @return array
 */
function getApplicationStatus()
{
    $result = \ABI\classes\Validator::checkApplicationStatus();
    return $result;
}

/**
 * @return array
 */
function getDBParams()
{
    $result = array (
        'is_db_enable' => \ABI\Settings::getParam('is_db_enable'),
        'host'         => \ABI\Settings::getParam('host'),
        'driver'       => \ABI\Settings::getParam('driver'),
        'login'        => \ABI\Settings::getParam('login'),
        'password'     => \ABI\Settings::getParam('password'),
        'db_name'      => \ABI\Settings::getParam('db_name'),
        'prefix'       => \ABI\Settings::getParam('prefix')
    );
    return $result;
}

/**
 * @return array
 */
function getLoggerParams()
{
    $current_dir = __DIR__;
    $current_dir_arr = explode(DIRECTORY_SEPARATOR, $current_dir);
    array_pop($current_dir_arr);
    $relative_log_path = implode(DIRECTORY_SEPARATOR, $current_dir_arr);

    $result = array (
        'is_logging_enable'          => \ABI\Settings::getParam('is_logging_enable'),
        'logfile_name'               => \ABI\Settings::getParam('logfile_name'),
        'logfile_create_after_hours' => \ABI\Settings::getParam('logfile_create_after_hours'),
        'log_level'                  => \ABI\Settings::getParam('log_level'),
        'log_path'                   => \ABI\Settings::getParam('log_path'),
        'is_logging_trace'           => \ABI\Settings::getParam('is_logging_trace'),
        'current_log_path'           => \ABI\Logger::getFullLogPath(),
        'relative_log_path'          => $relative_log_path
    );
    return $result;
}

/**
 * @return array
 */
function getAvailableTypes()
{
    $result = array (
        'available_types'  => \ABI\classes\Types::getAvailableTypes(),
        'logger_levels'    => \ABI\Logger::getAllLoggerLevels(),
        'database_drivers' => \ABI\classes\Parser::getAllDatabaseDrivers()
    );
    return $result;
}

/**
 * @param  object  $parameters  A name of a model
 * @return array
 */
function deleteModel($parameters)
{
	$model_name = $parameters->modelName;
    $model_bind_db = $parameters->bindDB;
    \ABI\classes\Parser::deleteModelPattern($model_name, $model_bind_db);
    $result = array ('success' => 'true');
    return $result;
}

/**
 * @param  object  $parameters  JSON with logger parameters
 * @return array
 */
function updateLoggerSettings($parameters)
{
    if (
        '' !== $parameters->logfile_name &&
        !preg_match(\ABI\Settings::getParam('pattern_logfile_name'), $parameters->logfile_name)
    ) {
        \ABI\EventHandler::validationError("The name of logger file '" . $parameters->logfile_name . "' unavailable");
    }

    \ABI\Settings::update('logger', $parameters);
    $result = array ('ABI_success_message' => 'The settings parameters was updated');
    return $result;
}

function getLastLoggerMessages()
{
    $result = \ABI\Logger::getLastLoggerMessages();
    return $result;
}

/**
 * @param  object  $parameters  JSON with database parameters
 * @return array
 */
function updateDBSettings($parameters)
{
    \ABI\Settings::update('database', $parameters);
    $result = array ('ABI_success_message' => 'The settings parameters was updated');
    return $result;
}

/**
 * @param  object  $parameters  JSON with database parameters
 * @return array
 */
function checkDBSettings($parameters)
{
    try {
        $driver   = $parameters->driver;
        $host     = $parameters->host;
        $login    = $parameters->login;
        $password = $parameters->password;

        new \PDO("$driver:host=$host;charset=utf8", "$login", "$password");
    } catch (\PDOException $e) {
        \ABI\EventHandler::error($e->getMessage());
    }

    $result = array ('ABI_success_message' => 'The connection to database is established successfully');
    return $result;
}

/**
 * @return array
 */
function getModelList()
{
    $result = \ABI\classes\Parser::getAllModelPattern();
    return $result;
}

/**
 * @param  object  $parameters  JSON with a model fields
 * @return array
 */
function setModel($parameters)
{
    \ABI\classes\Parser::setModelPattern($parameters);
    $result = array ('success' => 'true');
    return $result;
}

/**
 * @param  object  $parameters  JSON with a model fields
 * @return array
 */
function updateModel($parameters)
{
    \ABI\classes\Parser::updateModelPattern($parameters);
    $result = array ('success' => 'true');
    return $result;
}

/**
 * @param  object  $parameters  JSON with a model field
 * @return array
 */
function validateModelField($parameters)
{
    \ABI\classes\Parser::validateFrontendModelField($parameters);
    $result = array ('success' => 'true');
    return $result;
}
