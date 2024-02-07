<?php

namespace NeoCube;

use NeoCube\Application;
use NeoCube\Error\ErrorAbstract;
use NeoCube\Error\ErrorType;

class Error extends ErrorAbstract {

    public function onError(){
        $error = $this->error;
        $type  = $this->type;
        if ( ErrorType::SHUTDOWN == $type ) return null;
        if (Application::isEnvironment(Application::ENVIRONMENT_PRODUCTION)) {
            ob_clean();
            header('Content-Type: text/plain');
            http_response_code(500);
            header('status: 500');
            echo 'Internal Server Error! Sorry!';
            exit(0);
        } else {
            echo "NEOCUBE ERROR -> {$type->name}\n";
            echo "Error\n";
            echo "[{$error['code']}] {$error['message']}\n";
            echo "Fatal error on line {$error['line']} in file {$error['file']}\n";
            echo "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
        }
    }

}
