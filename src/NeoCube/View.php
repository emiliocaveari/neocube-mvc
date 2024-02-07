<?php

namespace NeoCube;

use NeoCube\Error\ErrorType;
use \NeoCube\Router;

class View {

    private string|bool $_layout = 'default';
    private string|bool $_view   = 'index';
    private array  $_path   = ['view'=>'','layout'=>''];

    private string $_controller;
    private string $_action;
    private array  $_data   = [];

    private array $_link   = [];
    private array $_meta   = [];
    private array $_script = [];
    private array $_style  = [];

    private ?string $_renderized = null;


    //--Construtor --//
    final public function __construct(?string $view='',null|string|array $path=null,array $data=[]) {
        $this->_view = $view;
        if (!empty($path)){
            if (is_string($path)) $this->_path['view'] = $path;
            else {
                $this->_path['view'] = $path['view'] ?? $path[0];
                $this->_path['layout'] = $path['layout'] ?? $path[1];
            }
        } else if (defined('NEOCUBE_VIEW_PATH') and defined('NEOCUBE_LAYOUT_PATH')){
            $this->_path['view']   = NEOCUBE_VIEW_PATH;
            $this->_path['layout'] = NEOCUBE_LAYOUT_PATH;
        }
        if ($data) $this->_data = $data;
    }

    //--Controllers--//
    final public function setController($controller,$action,$view_path){
        $this->_controller   = $controller;
        $this->_action       = $action;
        $this->_view         = $action;
        $this->_path['view'] = $view_path;
    }
    final public function setAction($action){
        $this->_view   = $action;
        $this->_action = $action;
    }
    final public function getController() { return $this->_controller; }
    final public function getAction() { return $this->_action;     }
    
    
    //--Set--//
    final public function setData($data,$value=null){
        if ( is_array($data) ){
            $this->_data = array_merge($this->_data,$data);
        } else {
            $this->_data[$data] = $value;
        }
        return $this;
    }
    final public function setLayout($layout,$path=false){
        $this->_layout = $layout;
        if ($path!==false) $this->_path['layout'] = $path ?: NEOCUBE_LAYOUT_PATH;
    }
    final public function setView($view,$path=false){
        $this->_view = $view;
        if ($path!==false) $this->_path['view'] = $path ?: NEOCUBE_VIEW_PATH;
    }
    final public function setPath(?string $view=null,?string $layout=null){
        $this->_path['view'] = $view   ?: NEOCUBE_VIEW_PATH;
        $this->_path['layout'] = $layout ?: NEOCUBE_LAYOUT_PATH;
    }


    //--Get--//
    final public function getLayout() { return $this->_layout; }
    final public function getView() { return $this->_view; }
    final public function getData(?string $val=null) {
        if ( is_null($val) ) return $this->_data;
        else return $this->_data[$val] ?? null;
    }


    //--No view / Layout--//
    final public function noLayout()  { $this->_layout = false; }
    final public function noView()    { $this->_view   = false; }
    final public function clearData() { $this->_data   = []; }

    //--Tratamento de style ,script e tag meta na view
    final public function linkTag (string $link,array $attributes=[]){
        if(substr($link,0,4) != 'http') $link = Router::getPublicDir().$link;
        $attr = array('href'=>$link,'type'=>'text/css','rel'=>'stylesheet');
        if ($attributes) $attr = array_merge($attr,$attributes);
        $str = '';
        foreach ($attr as $key => $value) $str .= "{$key}=\"{$value}\" ";
        return "<link {$str} />";
    }
    final public function metaTag (array $attributes=[]){
        $str = '';
        foreach ($attributes as $key => $value) $str .= "{$key}=\"{$value}\" ";
        return "<meta {$str} />";
    }
    final public function scriptTag (string $scr,array $attributes=[]){
        if(substr($scr,0,4) != 'http') $scr = Router::getPublicDir().$scr;
        $attr = array('src'=>$scr,'type'=>'text/javascript');
        if ($attributes) $attr = array_merge($attr,$attributes);
        $str = '';
        foreach ($attr as $key => $value) $str .= "{$key}=\"{$value}\" ";
        return [
            'tag'     => "<script {$str} ></script>",
            'attr'    => $str,
            'content' => '',
        ];
    }
    final public function styleTag (string $scr,array $attributes=[]){
        if(substr($scr,0,4) != 'http') $scr = Router::getPublicDir().$scr;
        $attr = array('src'=>$scr,'type'=>'text/css');
        if ($attributes) $attr = array_merge($attr,$attributes);
        $str = '';
        foreach ($attr as $key => $value) $str .= "{$key}=\"{$value}\" ";
        return [
            'tag'     => "<style {$str} ></style>",
            'attr'    => $str,
            'content' => '',
        ];
    }

    //--Adiciona Tags para renderizar no layout
    final public function addLinkTag   ($link,array $attr=[]){ $this->_link[]   = $this->linkTag($link,$attr); }
    final public function addScriptTag ($link,array $attr=[]){ $this->_script[] = $this->scriptTag($link,$attr); }
    final public function addStyleTag  ($link,array $attr=[]){ $this->_style[] = $this->styleTag($link,$attr); }
    final public function addMetaTag   (array $attr=[])      { $this->_meta[]   = $this->metaTag($attr);         }

    final public function exportTags(){
        return [
            'link'   => $this->_link,
            'meta'   => $this->_meta,
            'script' => $this->_script,
            'style'  => $this->_style,
        ];
    }
    final public function importTags(array $tags){
        foreach ($tags['link'] as $value)   $this->_link[] = $value;
        foreach ($tags['meta'] as $value)   $this->_meta[] = $value;
        foreach ($tags['script'] as $value) $this->_script[] = $value;
        foreach ($tags['style'] as $value)  $this->_style[] = $value;
    }


    //--Renderiza Tags adicionadas --//
    final public function renderLinkTag() :string {
        return ( $this->_link ) ? implode(PHP_EOL,$this->_link) : '';
    }
    final public function renderMetaTag() :string{
        return ( $this->_meta ) ? implode(PHP_EOL,$this->_meta) : '';
    }
    final public function renderScriptTag() :string{
        return ( $this->_script ) ? implode(PHP_EOL,array_map(function($tag){ return $tag['tag']; },$this->_script)) : '';
    }
    final public function renderStyleTag() :string{
        return ( $this->_style ) ? implode(PHP_EOL,array_map(function($tag){ return $tag['tag']; },$this->_style)) : '';
    }

    //--Renderiza uma view diretamente na view atual --//
    final public function renderView(View|string|array $view, bool|array $data=false,bool $exportTags=true) :string {
        if ( ! $view instanceof View){
            $path = '';
            if ( is_array($view) ) list($viewname,$path) = $view;
            else $viewname = $view;
            
            $view = new static($viewname,$path);
        }
        if ($data){
            if (is_array($data)) $view->setData($data);
            else                 $view->setData($this->_data);
        }
        $renderHTML = $view->render($exportTags);
        if ( $exportTags ) $this->importTags($view->exportTags());
        
        return $renderHTML;
    }

    //--Renderiza a view atual
    //--Geralmente usado dentro do layout para renderizar a view
    final public function render(bool $tagsExport=false) : ?string {
        $this->viewRender($tagsExport);
        return $this->_renderized;
    }

    //--Exibe layout - Chamado pelo controller
    final public function renderAll() :string {
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
    private function viewRender(bool $tagsExport=false) : void{
        if ($this->_renderized===null and $this->_view!==false ){
            $this->_renderized='';

            if ( substr($this->_path['view'],-1,1) !== '/')
                $viewFile = $this->_path['view'] . '/' . $this->_view;
            else 
                $viewFile = $this->_path['view'] . $this->_view;

            //--Arquino da view a ser requerido
            if (substr($viewFile,-6) !== '.phtml') $viewFile .= '.phtml';

            if ( file_exists($viewFile) ){
                extract($this->_data,EXTR_PREFIX_SAME, "view");
                ob_start();
                require $viewFile;
                $this->_renderized = ob_get_contents();
                ob_end_clean();

                //--Se a renderização tem layout
                if ( $this->_layout !== false or $tagsExport){
                    //--Removendo tag script da View
                    $this->_renderized = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {
                        $this->_script[] = [
                            'tag'     => $matches[0],
                            'attr'    => $matches[1],
                            'content' => $matches[2],
                        ];
                        return '';
                    }, $this->_renderized);
                    //--Removendo link da View
                    $this->_renderized = preg_replace_callback('#<link(.*?)>#is', function ($matches) {
                        $this->_link[] = $matches[0];
                        return '';
                    }, $this->_renderized);
                    //--Removendo style da View
                    $this->_renderized = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function ($matches) {
                        $this->_style[] = [
                            'tag'     => $matches[0],
                            'attr'    => $matches[1],
                            'content' => $matches[2],
                        ];
                        return '';
                    }, $this->_renderized);
                }

                if ( strpos($this->_renderized,'{PUBLIC_DIR}') !== false ){
                    $this->_renderized = str_replace('{PUBLIC_DIR}',Router::getPublicUrl(),$this->_renderized);
                }

            } else {
                Application::ErrorReporting()->dispatch('View '.$viewFile.' não encontrado',ErrorType::WARNING);
            }
        }
    }

    //--Renderiza a o layout em _renderized
    private function layoutRender(){
        if ( $this->_layout !== false ){
            $layoutFile = $this->_path['layout'].$this->_layout . '.phtml';
            if (file_exists($layoutFile)){
                extract($this->_data,EXTR_PREFIX_SAME, "view");
                ob_start();
                require $layoutFile;
                $this->_renderized = ob_get_contents();
                ob_end_clean();
                if ( strpos($this->_renderized,'{PUBLIC_DIR}') !== false ){
                    $this->_renderized = str_replace('{PUBLIC_DIR}',Router::getPublicUrl(),$this->_renderized);
                }
            } else {
                exit('Layout "'.$this->_layout.'" nao encontrado em '. $this->_path['layout']);
            }
        }
    }


}
