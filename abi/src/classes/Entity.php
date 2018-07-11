<?php
/**
 * Author: Daniil Hryhorov
 * Email: daniil.grigorov.kh@gmail.com
 */

namespace ABI\classes;

use ABI\Settings;

class Entity
{
    private static function getSettingsInstance()
    {
        Settings::getInstance();
    }

	public static function getEntity($request_body, $model_name)
    {
        self::getSettingsInstance();
        $model = Parser::getModelPattern($model_name);
        $entity = Validator::checkPatternFields($request_body, $model, $model_name);
        return $entity;
	}
}
