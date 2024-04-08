<?php

namespace NeoCube;

use NeoCube\Render\RenderInterface;
use NeoCube\Render\ViewHtml;
use NeoCube\View;

abstract class Controller {

    protected ?View $view = null;

    private $_action = 'index';
    private $_controller;
    private $_values = [];

    public function _init(){}

    final public function setController($controller) :void {
        $this->_controller = strtolower($controller);
    }
    final public function setAction($action) :void {
        $this->_action = strtolower($action);
    }
    final public function setValues($values) :void {
        $this->_values = $values;
    }

    final public function getController($url=false)  {
        return $this->_controller;
    }
    final public function getAction() {
        return $this->_action;
    }
    final public function getValues() {
        return $this->_values;
    }

    protected function getViewPath(){
        //--View padrão do controller localizada na mesma pasta dentro de Views
        $reflector = new \ReflectionClass($this);
        return dirname($reflector->getFileName()) . '/Views/';
    }


    final public function execute() :RenderInterface {
        //--View relacionada ao controller
        if (!$this->view) $this->view = new View();
        $this->view->setController($this->_controller,$this->_action,$this->getViewPath());
        
        //--Executando ações
        $action = $this->_action.'_';
        $this->_init();
        return $this->render($this->$action(...$this->_values));
    }


    public function render(mixed $actionData) :RenderInterface {
        if ( $actionData instanceof RenderInterface)
            return $actionData;
        //--Retorna a rederização da view por padrão
        return new ViewHtml($this->view);
    }


}
