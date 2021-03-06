<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

class EventHandler
{
    private static function setStackTrace()
    {
        $stack_trace = array();

        if ('true' === Settings::getParam('is_logging_trace')) {
            $stack_trace = debug_backtrace();
            array_shift($stack_trace);
        }

        return $stack_trace;
    }

	public static function notice($msg)
    {
        $stack_trace = self::setStackTrace();
		Logger::addLogMessage($msg, 'Notice', $stack_trace);
	}

	public static function warning($msg)
    {
        $stack_trace = self::setStackTrace();
		Logger::addLogMessage($msg, 'Warning', $stack_trace);
	}

	public static function validationError($msg)
    {
        throw new ABIException($msg, 422);
    }

	public static function error($msg)
    {
        $stack_trace = self::setStackTrace();
		Logger::addLogMessage($msg, 'Error', $stack_trace);
		throw new ABIException($msg);
	}
}
