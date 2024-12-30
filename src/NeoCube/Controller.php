<?php

namespace NeoCube;

use NeoCube\View\ViewRenderInterface;
use NeoCube\View\RenderView;
use NeoCube\View;
use NeoCube\View\RenderJson;

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


    final public function execute(): ?ViewRenderInterface {
        if (!$this->view) $this->view = new View();
        $this->view->setController($this->_controller, $this->_action, $this->getViewPath());

        $action = $this->_action . '_';
        $this->_init();
        $actionData = $this->$action(...$this->_values);
        return $actionData!==false ? $this->render($actionData) : null;
    }


    public function render(mixed $actionData): ViewRenderInterface {
        if ($actionData instanceof ViewRenderInterface)
            return $actionData;
        if (is_array($actionData)) {
            $status = $actionData['status'] ?? 200;
            $data = $actionData['data'] ?? $actionData;
            return new RenderJson($data, $status);
        }
        if (is_object($actionData)) {
            $status = $actionData?->status ?? 200;
            $data = $actionData?->data ?? $actionData;
            return new RenderJson($data, $status);
        }
        //--Retorna a rederização da view por padrão
        return new RenderView($this->view);
    }
}
