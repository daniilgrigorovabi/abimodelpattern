<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\database;

use ABI\EventHandler;
use ABI\Settings;

class Database
{
	private $source;

    public function __construct()
    {
        if ('true' !== Settings::getParam('is_db_enable')) {
            EventHandler::error('The connection to database is needed to execute this request. You can enable it in the "Database" page');
        }

        $driver_name = Settings::getParam('driver');
        $driver = $this->getDriverClass($driver_name);
        $this->setSource($driver);
        $this->setConnection();
    }

	private function getDriverClass($driver_name)
    {
		$driver_name = Settings::getParam('driver_ns') . ucfirst($driver_name) . '_Driver';
		$driver = new $driver_name();
		return $driver;
	}

    private function setSource(IDatabase_Driver $source)
    {
        $this->source = $source;
    }

	private function setConnection()
    {
		try {
			$driver   = Settings::getParam('driver');
			$host     = Settings::getParam('host');
			$login    = Settings::getParam('login');
			$password = Settings::getParam('password');

			$db = new \PDO("$driver:host=$host;charset=utf8", "$login", "$password");
			$this->source->setConnection($db);
			$this->createMPDatabase();
		} catch (\PDOException $e) {
            EventHandler::error($e->getMessage());
		}
	}

    private function createMPDatabase()
    {
        $this->source->createMPDatabase(Settings::getParam('db_name'));
        $this->source->useMPDatabase(Settings::getParam('db_name'));
    }

	public function getLastError()
    {
		return $this->source->getLastError();
	}

	public function createMPTable($model)
    {
		return $this->source->createMPTable($model);
	}

	public function updateMPTable($model, $old_model, $map)
    {
		return $this->source->updateMPTable($model, $old_model, $map);
	}

	public function deleteMPTable($model_name)
    {
		return $this->source->deleteMPTable($model_name);
	}

	public function exportMPData($model_names)
    {
		return $this->source->exportMPData($model_names);
	}

	public function importMPData($model_names)
    {
		return $this->source->importMPData($model_names);
	}
}
