<?php

namespace NeoCube;

use NeoCube\Application;
use NeoCube\Error\ErrorAbstract;
use NeoCube\Error\ErrorType;

class Error extends ErrorAbstract {

    public function onError() {
        $error = $this->error;
        $type  = $this->type;
        if (ErrorType::SHUTDOWN == $type) return null;

        echo "NEOCUBE ERROR -> {$type->name}\n"
            . "[{$error['code']}] {$error['message']}\n"
            . "Fatal error on line {$error['line']} in file {$error['file']}\n"
            . "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
    }
}
