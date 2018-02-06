<?php
/**
 * Author: Daniil Grigorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI;

use ABI\classes\Parser;

class Logger {
	const NOTICE = 1;
	const WARNING = 2;
	const ERROR = 3;

	const DEFAULT_LOGFILE_NAME 		= 'testlogfile';
	const DEFAULT_HOURS_FOR_LOGFILE = 168;
	const DEFAULT_LOG_LEVEL 		= 'notice';

	public static function getAllLoggerLevels() {
		$logger_levels = array(
			'notice',
			'warning',
			'error'
		);

		return $logger_levels;
	}

	public static function addLogMessage($msg, $level, $stack_trace) {
		if(!is_string($msg)) {
            EventHandler::notice("The error message must be of type 'String'. ".ucfirst(gettype($msg)).' to string conversion in '.__FILE__);
		}

		$log_level = Settings::getParam('log_level');

		if(!defined('self::'.strtoupper($log_level))) {
			$log_level = self::DEFAULT_LOG_LEVEL;
		}

		if(!defined('self::'.strtoupper($level))) {
			EventHandler::error("The logger level '$level' not found");
		}

		if(constant('self::'.strtoupper($level)) >= constant('self::'.strtoupper($log_level))) {
			$logfile_name  = Settings::getParam('logfile_name') ?
							 Settings::getParam('logfile_name') :
							 self::DEFAULT_LOGFILE_NAME;

			// Check logger file name
			if(!preg_match(Settings::getParam('pattern_logfile_name'), $logfile_name)) {
				Settings::update('logger', array('logfile_name' => self::DEFAULT_LOGFILE_NAME));
				$logfile_name = self::DEFAULT_LOGFILE_NAME;
			}

			$hours_now = date("H");

			if($hours_now >= 24) {
				$hours_now = 0;
			}

			$hours_for_logfile = Settings::getParam('logfile_create_after_hours') * 1;

			if(!is_int($hours_for_logfile) && ($hours_for_logfile < 1 || $hours_for_logfile > 168)) {
				$hours_for_logfile = self::DEFAULT_HOURS_FOR_LOGFILE;
			}

			$count_day_files = explode('.', ($hours_now / $hours_for_logfile));
			$logfile_hours = $count_day_files[0] * $hours_for_logfile;
			$logfile_hours = str_pad($logfile_hours, 2, '0', STR_PAD_LEFT);

			$logfile_name .= '-'.date("Y-m-d").'-'.$logfile_hours.'-00.log';

			$log_file_path = Settings::getParam('log_path') ?
                             Parser::getPath('log_path') :
							 sys_get_temp_dir();

			$log_file_path .= DIRECTORY_SEPARATOR.$logfile_name;
			$date = date('d.m.Y h:i:s');

			$msg = $msg.PHP_EOL;

			if($stack_trace) { $msg .= 'Stack trace: '.json_encode($stack_trace).PHP_EOL; }

			$logMessage = $date.' | '.$level.': '.(string)$msg;
			error_log($logMessage, 3, $log_file_path);
			chmod($log_file_path, 0777);
		}
	}
}
