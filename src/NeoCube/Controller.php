<?php

namespace NeoCube;

use NeoCube\View\ViewRenderInterface;
use NeoCube\View\RenderHtml;
use NeoCube\View;

abstract class Controller {

    protected ?View $view = null;

    protected string $_action = 'index';
    protected string $_controller = '';
    private array $_values = [];

    public function _init() {
    }

    final public function setController(string $controller): void {
        $this->_controller = $controller;
    }
    final public function setAction(string $action): void {
        $this->_action = $action;
    }
    final public function setValues($values): void {
        $this->_values = $values;
    }

    final public function getController($url = false) {
        return $this->_controller;
    }
    final public function getAction() {
        return $this->_action;
    }
    final public function getValues() {
        return $this->_values;
    }

    protected function getViewPath() {
        $reflector = new \ReflectionClass($this);
        return dirname($reflector->getFileName()) . '/Views/';
    }


    final public function execute(): ViewRenderInterface {
        if (!$this->view) $this->view = new View();
        $this->view->setController($this->_controller, $this->_action, $this->getViewPath());

        $action = $this->_action . '_';
        $this->_init();
        return $this->render($this->$action(...$this->_values));
    }


    public function render(mixed $actionData): ViewRenderInterface {
        if ($actionData instanceof ViewRenderInterface)
            return $actionData;
        return new RenderHtml($this->view);
    }
}
