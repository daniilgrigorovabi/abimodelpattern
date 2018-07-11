<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes\database;

interface IDatabase_Driver
{
	public function getLastError();

    public function createMPDatabase($db_name);

    public function useMPDatabase($db_name);

	public function createMPTable($model);

	public function updateMPTable($model, $old_model, $map);

	public function deleteMPTable($model_name);

	public function exportMPData($model_names);

	public function importMPData($model_names);

	public function setConnection($db);
}
