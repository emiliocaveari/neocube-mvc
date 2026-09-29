<?php

namespace NeoCube\Application;

use NeoCube\Error\ErrorAbstract;
use NeoCube\Router;

class ApplicationContext {

    public ?Router $router = null;
    public ?ErrorAbstract $error = null;
}
