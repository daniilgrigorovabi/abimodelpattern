<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

/**
 * @return array
 */
function getApplicationStatus() {
    $result = \ABI\classes\Validator::checkApplicationStatus();
    return $result;
}

/**
 * @return array
 */
function getDBParams() {
    $result = array(
        'host'     => \ABI\Settings::getParam('host'),
        'driver'   => \ABI\Settings::getParam('driver'),
        'login'    => \ABI\Settings::getParam('login'),
        'password' => \ABI\Settings::getParam('password'),
        'db_name'  => \ABI\Settings::getParam('db_name'),
        'prefix'   => \ABI\Settings::getParam('prefix')
    );
    return $result;
}

/**
 * @return array
 */
function getLoggerParams() {
    $result = array(
        'logfile_name'               => \ABI\Settings::getParam('logfile_name'),
        'logfile_create_after_hours' => \ABI\Settings::getParam('logfile_create_after_hours'),
        'log_level'                  => \ABI\Settings::getParam('log_level'),
        'log_path'                   => \ABI\Settings::getParam('log_path'),
        'is_logging_trace'           => \ABI\Settings::getParam('is_logging_trace'),
    );
    return $result;
}

/**
 * @return array
 */
function getAvailableTypes() {
    $result = array(
        'available_types'  => \ABI\classes\Types::getAvailableTypes(),
        'logger_levels'    => \ABI\Logger::getAllLoggerLevels(),
        'database_drivers' => \ABI\classes\Parser::getAllDatabaseDrivers()
    );
    return $result;
}

/**
 * @param  string  $parameters  A name of a model
 * @return array
 */
function deleteModel($parameters) {
    \ABI\classes\Parser::deleteModelPattern($parameters);
    $result = array('success' => 'true');
    return $result;
}

/**
 * @param  string  $parameters  JSON with logger parameters
 * @return array
 */
function updateLoggerSettings($parameters) {
    $body = json_decode($parameters);
    \ABI\Settings::update('logger', $body);
    $result = array('ABI_success_message' => 'The settings parameters was updated');
    return $result;
}

/**
 * @param  string  $parameters  JSON with database parameters
 * @return array
 */
function updateDBSettings($parameters) {
    $body = json_decode($parameters);
    \ABI\Settings::update('database', $body);
    $result = array('ABI_success_message' => 'The settings parameters was updated');
    return $result;
}

/**
 * @return array
 */
function getModelList() {
    $result = \ABI\classes\Parser::getAllModelPattern();
    return $result;
}

/**
 * @param  string  $parameters  JSON with a model fields
 * @return array
 */
function setModel($parameters) {
    $body = json_decode($parameters);
    \ABI\classes\Parser::setModelPattern($body);
    $result = array('success' => 'true');
    return $result;
}

/**
 * @param  string  $parameters  JSON with a model fields
 * @return array
 */
function updateModel($parameters) {
    $body = json_decode($parameters);
    \ABI\classes\Parser::updateModelPattern($body);
    $result = array('success' => 'true');
    return $result;
}

/**
 * @param  string  $parameters  JSON with a model field
 * @return array
 */
function validateModelField($parameters) {
    $body = json_decode($parameters);
    \ABI\classes\Parser::validateFrontendModelField($body);
    $result = array('success' => 'true');
    return $result;
}
