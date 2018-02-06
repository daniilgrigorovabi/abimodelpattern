# ABI Model Pattern
This library is intended to create models with customized parameters and manage them in the future, as well as to provide an entity, based on the model, with the possibility of updating fields, including the check of the value in interactive.

## How to use
Open the index.html file from the library folder in browser to get the graphical interface. In this interface you may to create models and configure Database/Logger settings.

### Quick Start
```php
// Include the composer autoloader
require_once('PATH_TO_ABI_LIBRARY/abi/vendor/autoload.php');

\ABI\Settings::getInstance();

$body = json_decode('REQUEST BODY');
$model = \ABI\classes\Parser::getModelPattern('MODEL_NAME');
$entity = \ABI\classes\Validator::checkPatternFields($body, $model);
```
![abi_model_pattern](https://i.imgur.com/iDHVeZ7.png)

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details