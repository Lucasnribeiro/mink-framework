<?php

namespace Mink\Helper;

class Dotenv {

    public static function buildEnvVariables(){
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
          $envVars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
          foreach ($envVars as $key => $value) {
            if (strpos($value, ',') !== false) {
              // If the value contains a comma, it's an array variable.
              // Convert the value to an array and set it as the variable value.
              $arrayValue = explode(',', $value);
              putenv("$key=".implode(',', $arrayValue));
              $_ENV[$key] = $arrayValue;
              $_SERVER[$key] = $arrayValue;
            } else {
              // If the value doesn't contain a comma, it's a regular variable.
              putenv("$key=$value");
              $_ENV[$key] = $value;
              $_SERVER[$key] = $value;
            }
          }
        }
    }

}