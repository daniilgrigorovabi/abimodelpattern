# ABI Model Pattern
This library is intended to create models with customized parameters and manage them in the future, as well as to provide an entity, based on the model, with the possibility of updating fields, including the check of the value in interactive.

## Installation
* Download the latest release [https://github.com/daniilgrigorovabi/abimodelpattern/releases/latest](https://github.com/daniilgrigorovabi/abimodelpattern/releases/latest)
* Extract the downloaded archive to the path where you want to install the library.
* To install the defined dependencies for this project, run the install command from the "abi" folder of the library.
```
composer install
```
* Make sure to enable Apache’s mod_rewrite module and check your virtual host is configured with the AllowOverride option.
* Check and set, if needed, permissions of the library config file to world-readable and world-writable (0666).
* Once the library is installed use the **username** - admin and **password** - admin to log in to the graphical user interface of.

## How to use
Open the index.html file from the library folder in browser to get the graphical interface. In this interface you may to create models and configure Database/Logger settings.

### Quick Start
```php
// Include the composer autoloader
require_once('PATH_TO_ABI_LIBRARY/abi/vendor/autoload.php');

$body   = json_decode('REQUEST BODY');
$entity = \ABI\classes\Entity::getEntity($body, 'MODEL_NAME');
```

### Example

Model "ATM"
![abi_model_pattern_atm](https://i.imgur.com/zl8jWvG.png)

Model "ATMdevice"
![abi_model_pattern_atm_device](https://i.imgur.com/d0TOeUJ.png)

```php
try {
    // Include the composer autoloader
    require_once('PATH_TO_ABI_LIBRARY/abi/vendor/autoload.php');

    $prv24_atms_url = 'https://api.privatbank.ua/p24api/infrastructure?json&atm&address=&city=%D0%96%D0%BE%D0%BB%D0%BA%D0%B2%D0%B0';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $prv24_atms_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $prv24_response = curl_exec($ch);
    curl_close($ch);
    
    $request_body = json_decode($prv24_response);
    $available_atms = \ABI\classes\Entity::getEntity($request_body, 'atm');
```

![abi_model_pattern_response_atm](https://i.imgur.com/SuZDiiN.png)

```php
    $atm_devices = $available_atms->devices;
```

![abi_model_pattern_response_atm_devices](https://i.imgur.com/b6dCscL.png)

```php
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
```

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details