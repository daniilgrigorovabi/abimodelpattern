<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

use ABI\classes\Parser;

class Logger
{
	const NOTICE = 1;
	const WARNING = 2;
	const ERROR = 3;

	const DEFAULT_LOGFILE_NAME 		= 'testlogfile';
	const DEFAULT_HOURS_FOR_LOGFILE = 168;
	const DEFAULT_LOG_LEVEL 		= 'notice';

	public static function getAllLoggerLevels()
    {
		$logger_levels = array (
			'notice',
			'warning',
			'error'
		);

		return $logger_levels;
	}

	public static function getFullLogPath() {
        $log_file_path = Settings::getParam('log_path') ?
            Parser::getPath('log_path') :
            sys_get_temp_dir();

        return $log_file_path;
    }

    public static function getLogFileName()
    {
        $logfile_name  = Settings::getParam('logfile_name') ?
            Settings::getParam('logfile_name') :
            self::DEFAULT_LOGFILE_NAME;

        // check logger file name
        if (!preg_match(Settings::getParam('pattern_logfile_name'), $logfile_name)) {
            Settings::update('logger', array ('logfile_name' => self::DEFAULT_LOGFILE_NAME));
            $logfile_name = self::DEFAULT_LOGFILE_NAME;
        }

        $hours_now = date("H");

        if ($hours_now >= 24) {
            $hours_now = 0;
        }

        $hours_for_logfile = Settings::getParam('logfile_create_after_hours') * 1;

        if (!is_int($hours_for_logfile) && ($hours_for_logfile < 1 || $hours_for_logfile > 168)) {
            $hours_for_logfile = self::DEFAULT_HOURS_FOR_LOGFILE;
        }

        $count_day_files = explode('.', ($hours_now / $hours_for_logfile));
        $logfile_hours = $count_day_files[0] * $hours_for_logfile;
        $logfile_hours = str_pad($logfile_hours, 2, '0', STR_PAD_LEFT);

        $logfile_name .= '-' . date("Y-m-d") . '-' . $logfile_hours . '-00.log';

        return $logfile_name;
    }

	public static function addLogMessage($msg, $level, $stack_trace)
    {
        if ('true' !== Settings::getParam('is_logging_enable')) {
            return;
        }

		if (!is_string($msg)) {
            EventHandler::notice(
                "The error message must be of type 'String'. " .
                ucfirst(gettype($msg)).' to string conversion in ' .
                __FILE__
            );
		}

		$log_level = Settings::getParam('log_level');

		if (!defined('self::' . strtoupper($log_level))) {
			$log_level = self::DEFAULT_LOG_LEVEL;
		}

		if (!defined('self::' . strtoupper($level))) {
			EventHandler::error("The logger level '$level' not found");
		}

		if (constant('self::' . strtoupper($level)) >= constant('self::' . strtoupper($log_level))) {
			$log_file_path = self::getFullLogPath();

			if (sys_get_temp_dir() !== $log_file_path && !file_exists($log_file_path)) {
                mkdir($log_file_path, 0755, true);
            }

            $log_file_path .= DIRECTORY_SEPARATOR . self:: getLogFileName();

			$date = date('d.m.Y h:i:s');

			$msg = $msg . PHP_EOL;

			if ($stack_trace) {
			    $msg .= 'Stack trace: ' . json_encode($stack_trace) . PHP_EOL;
			}

			$logMessage = $date . ' | '.$level.': ' . (string)$msg;
			error_log($logMessage, 3, $log_file_path);
			chmod($log_file_path, 0777);
		}
	}

	public static function getLastLoggerMessages() {
        $log_file_path = self::getFullLogPath() . DIRECTORY_SEPARATOR . self:: getLogFileName();

        $logger_messages = array();
        $date_format = date("d.m.Y");

        if ($fh = fopen($log_file_path, 'r')) {
            fseek($fh, -4096, SEEK_END);

            while (!feof($fh)) {
                $buffer = rtrim(fgets($fh), PHP_EOL);
                if (
                    strpos($buffer, $date_format) !== false ||
                    (strpos($buffer, 'Stack trace:') !== false && !empty($logger_messages))
                ) {
                    $logger_messages[] = $buffer;
                }
            }

            fclose($fh);
        }

        return $logger_messages;
    }
}
