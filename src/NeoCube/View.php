<?php

namespace NeoCube;

use Closure;
use NeoCube\Error\ErrorType;
use NeoCube\Router;
use NeoCube\View\Tag\ScriptTag;

class View {

    private string|bool $_layout = 'default';
    private string|bool $_view   = 'index';
    private array  $_path   = ['view' => '', 'layout' => ''];

    private string $_controller;
    private string $_action;
    private array  $_data   = [];

    private array $_link   = [];
    private array $_meta   = [];
    private array $_script = [];
    private array $_style  = [];

    private ?string $_renderized = null;

    //--Construtor --//
    final public function __construct(?string $view = '', null|string|array $path = null, array $data = []) {
        $this->_view = $view;
        if (!empty($path)) {
            if (is_string($path)) $this->_path['view'] = $path;
            else {
                $this->_path['view'] = $path['view'] ?? $path[0];
                $this->_path['layout'] = $path['layout'] ?? $path[1];
            }
        } else if ($paths = Env::getValues(['view' => 'VIEW_PATH', 'layout' => 'LAYOUT_PATH'])) {
            $this->_path['view']   = $paths['view'] ?: '';
            $this->_path['layout'] = $paths['layout'] ?: '';
        }
        if ($data) $this->_data = $data;
    }

    //--Controllers--//
    final public function setController(string $controller, string $action, string $view_path) {
        $this->_controller   = $controller;
        $this->_action       = $action;
        $this->_view         = $action;
        $this->_path['view'] = $view_path;
    }
    final public function setAction(string $action) {
        $this->_view   = $action;
        $this->_action = $action;
    }
    final public function getController() {
        return $this->_controller;
    }
    final public function getAction() {
        return $this->_action;
    }


    //--Set--//
    final public function setData(mixed $data, mixed $value = null) {
        if (is_array($data)) {
            $this->_data = array_merge($this->_data, $data);
        } else {
            $this->_data[$data] = $value;
        }
        return $this;
    }
    final public function setLayout(string $layout, string|null|false $path = false) {
        $this->_layout = $layout;
        if ($path !== false) $this->_path['layout'] = $path ?: Env::getValue('LAYOUT_PATH');
    }
    final public function setView(string $view, string|null|false $path = false) {
        $this->_view = $view;
        if ($path !== false) $this->_path['view'] = $path ?: Env::getValue('VIEW_PATH');
    }
    final public function setPath(?string $view = null, ?string $layout = null) {
        $this->_path['view'] = $view   ?: Env::getValue('VIEW_PATH');
        $this->_path['layout'] = $layout ?: Env::getValue('LAYOUT_PATH');
    }


    //--Get--//
    final public function getLayout() {
        return $this->_layout;
    }
    final public function getView() {
        return $this->_view;
    }
    final public function getData(?string $val = null) {
        if (is_null($val)) return $this->_data;
        else return $this->_data[$val] ?? null;
    }


    //--No view / Layout--//
    final public function noLayout() {
        $this->_layout = false;
    }
    final public function noView() {
        $this->_view   = false;
    }
    final public function clearData() {
        $this->_data   = [];
    }

    //--Adiciona Tags para renderizar no layout
    final public function addLinkTag(string $link, array $attr = []) {
        $attr['src'] = Router::createLink($link);
        $this->_link[] = new ScriptTag(type: 'link', attributes: $attr);
    }
    final public function addScriptTag($link, array $attr = []) {
        $attr['src'] = Router::createLink($link);
        $this->_script[] = new ScriptTag(type: 'script', attributes: $attr);
    }
    final public function addStyleTag($link, array $attr = []) {
        $attr['src'] = Router::createLink($link);
        $this->_style[] = new ScriptTag(type: 'style', attributes: $attr);
    }
    final public function addMetaTag(array $attr = []) {
        $this->_meta[] = new ScriptTag(type: 'meta', attributes: $attr);
    }

    final public function exportTags() {
        return [
            'link'   => $this->_link,
            'meta'   => $this->_meta,
            'script' => $this->_script,
            'style'  => $this->_style,
        ];
    }
    final public function importTags(array $tags) {
        foreach ($tags['link'] as $value)   $this->_link[] = $value;
        foreach ($tags['meta'] as $value)   $this->_meta[] = $value;
        foreach ($tags['script'] as $value) $this->_script[] = $value;
        foreach ($tags['style'] as $value)  $this->_style[] = $value;
    }


    //--Renderiza Tags adicionadas --//
    private function renderScript(array $scripts, ?Closure $render = null, ?Closure $filter = null) {
        if ($filter) $scripts = array_filter($scripts, $filter);
        $redered = $render ? array_map($render, $scripts) : array_map(fn($s) => $s->render(), $scripts);
        return implode(PHP_EOL, $redered);
    }
    final public function renderScriptTag(?Closure $render = null, ?Closure $filter = null): string {
        return $this->renderScript($this->_script, $render, $filter);
    }
    final public function renderLinkTag(?Closure $render = null, ?Closure $filter = null): string {
        return $this->renderScript($this->_link, $render, $filter);
    }
    final public function renderMetaTag(?Closure $render = null, ?Closure $filter = null): string {
        return $this->renderScript($this->_meta, $render, $filter);
    }
    final public function renderStyleTag(?Closure $render = null, ?Closure $filter = null): string {
        return $this->renderScript($this->_style, $render, $filter);
    }

    //--Renderiza uma view diretamente na view atual --//
    final public function renderView(View|string|array $view, bool|array $data = false, bool $exportTags = true): string {
        if (!$view instanceof View) {
            $path = '';
            if (is_array($view)) list($viewname, $path) = $view;
            else $viewname = $view;

            $view = new static($viewname, $path);
        }
        if ($data) {
            if (is_array($data)) $view->setData($data);
            else                 $view->setData($this->_data);
        }
        $renderHTML = $view->render($exportTags);
        if ($exportTags) $this->importTags($view->exportTags());

        return $renderHTML;
    }

    //--Renderiza a view atual
    //--Geralmente usado dentro do layout para renderizar a view
    final public function render(bool $tagsExport = false): ?string {
        $this->viewRender($tagsExport);
        return $this->_renderized;
    }

    //--Exibe layout - Chamado pelo controller
    final public function renderAll(): string {
        //--renderiza view
        $this->viewRender();
        //--renderiza layout
        $this->layoutRender();
        //--retorna resultado para o controller
        return $this->_renderized;
    }

    //--funcoes privadas da view----------------------------------------------//
    //------------------------------------------------------------------------//

    //--Renderiza a view em _renderized
    private function viewRender(bool $tagsExport = false): void {
        if ($this->_renderized === null and $this->_view !== false) {
            $this->_renderized = '';

            if (substr($this->_path['view'], -1, 1) !== '/')
                $viewFile = $this->_path['view'] . '/' . $this->_view;
            else
                $viewFile = $this->_path['view'] . $this->_view;

            //--Arquino da view a ser requerido
            if (substr($viewFile, -6) !== '.phtml') $viewFile .= '.phtml';

            if (file_exists($viewFile)) {
                extract($this->_data, EXTR_PREFIX_SAME, "view");
                ob_start();
                require $viewFile;
                $this->_renderized = ob_get_contents();
                ob_end_clean();

                //--Se a renderização tem layout ou exportar as tags
                if ($this->_layout !== false or $tagsExport) {
                    //--Removendo link da View
                    $this->_renderized = preg_replace_callback('#<link(.*?)>#is', function ($matches) {
                        $this->_link[] = new ScriptTag(type: 'link', attributes: $matches[1]);
                        return '';
                    }, $this->_renderized);
                    //--Removendo metatag da View
                    $this->_renderized = preg_replace_callback('#<meta(.*?)>#is', function ($matches) {
                        $this->_meta[] = new ScriptTag(type: 'meta', attributes: $matches[1]);
                        return '';
                    }, $this->_renderized);
                    //--Removendo tag script da View
                    $this->_renderized = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {
                        $this->_script[] = new ScriptTag(type: 'script', content: $matches[2], attributes: $matches[1]);
                        return '';
                    }, $this->_renderized);
                    //--Removendo style da View
                    $this->_renderized = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function ($matches) {
                        $this->_style[] = new ScriptTag(type: 'style', content: $matches[2], attributes: $matches[1]);
                        return '';
                    }, $this->_renderized);
                }
            } else {
                Application::ErrorReporting()->dispatch('View ' . $viewFile . ' não encontrado', ErrorType::WARNING);
            }
        }
    }

    //--Renderiza a o layout em _renderized
    private function layoutRender() {
        if ($this->_layout !== false) {
            $layoutFile = $this->_path['layout'] . $this->_layout . '.phtml';
            if (file_exists($layoutFile)) {
                extract($this->_data, EXTR_PREFIX_SAME, "view");
                ob_start();
                require $layoutFile;
                $this->_renderized = ob_get_contents();
                ob_end_clean();
            } else {
                exit('Layout "' . $this->_layout . '" nao encontrado em ' . $this->_path['layout']);
            }
        }
    }
}
