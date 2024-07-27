<?php

namespace NeoCube;

use NeoCube\Application;
use NeoCube\Controller;
use NeoCube\Request;
use NeoCube\Error\ErrorType;
use NeoCube\Helper\Strings;
use NeoCube\Render\RenderInterface;

class Router {

    const MODE_URL_BASE     = 'url';
    const MODE_URL_REQUIRED = 'urlRequired';
    const MODE_URL_EXPLODE  = 'urlExplode';

    static protected array $url = [];
    static protected string $publicDir = '/';

    protected RenderInterface $outView;
    protected Controller $Controller;

    protected array $routes = array();

    protected string $controllerClassLoad = '\App\%s\Controller';


    //--STATIC FUNCTIONS--------------------------------------------//
    //--------------------------------------------------------------//


    static public function getUrl(string $mode = self::MODE_URL_REQUIRED): mixed {
        return self::$url[$mode] ?? self::$url[self::MODE_URL_REQUIRED];
    }

    static public function getPublicUrl(): string {
        return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . static::$publicDir;
    }


    static public function getPublicDir(): string {
        return static::$publicDir;
    }
    static public function setPublicDir($public): void {
        if (substr($public, -1, 1) != '/') $public .= '/';
        static::$publicDir = $public;
        self::urlGenerate();
    }

    //--Cria um link
    static public function createLink(null|string $str_router = null, array|bool $get_params = false, bool $merge_params = true) {
        $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . static::$publicDir;

        if ($str_router === null)
            $url .= self::$url[self::MODE_URL_BASE];
        else if (!empty($str_router)) {
            $str_router = implode('/', array_filter(explode('/', $str_router)));
            $url .= $str_router;
        }

        if (substr($url, -1, 1) == '/') $url = substr($url, 0, -1);

        if ($get_params) {
            if (!is_array($get_params)) $get_params = [];
            if ($merge_params) {
                if (Request::isMethod('get')) {
                    $data       = Request::getData();
                    $get_params = array_merge($data, $get_params);
                }
            }
            if ($get_params) $url .= '?' . http_build_query($get_params);
        }

        return $url;
    }

    //--Redireciona pagina
    static public function redirect(array|string $controller, string|null $action = null, array|string|null $params = null): void {

        if (is_array($controller)) {
            $aux = $controller;
            $controller = array_shift($aux);
            if (count($aux)) $action = array_shift($aux);
            if (count($aux)) $params = array_values($aux);
        } else if (substr($controller, 0, 4) == 'http') {
            $location = $controller;
        }

        if (!isset($location)) {
            if (!is_null($action)) $action = '/' . str_replace('/', '', $action);
            if (!is_null($params)) {
                if (is_array($params)) {
                    //--Verifica se é array sequencial ou associativo
                    if (array_keys($params) === range(0, count($params) - 1)) {
                        $params = '/' . implode('/', $params);
                    } else {
                        $params = '?' . http_build_query($params);
                    }
                } else $params = '/' . $params;
            }
            $location = static::$publicDir . $controller . $action . $params;
        }

        header("location: $location");
        exit();
    }


    //--CONTROLLERS--//
    //--Pasta da aplicação, onde encontrão-se os controllers
    public function setControllerClassLoad(string $controllerClassLoad): void {
        $this->controllerClassLoad = $controllerClassLoad;
    }
    public function getControllerClassLoad(): string {
        return $this->controllerClassLoad;
    }
    public function getController(): ?Controller {
        return $this->Controller;
    }

    //--PROTECTED FUNCTIONS--//
    //-----------------------//

    //--Seleciona controller definido pela url
    protected function selectController(): Controller {
        if ($this->routes) {
            return $this->getRouter();
        } else {
            return $this->sistemRouter();
        }
    }

    protected function urlToControllerClass(array $url): string {
        return Strings::toCamelCase(implode('\\', $url));
    }


    //--FINAL PUBLIC FUNCTIONS--//
    //--------------------------//
    final public function writeOut(): void {
        $this->outView->render();
    }

    final public function routing(): void {
        //--Realiza a leitur da URL
        if (empty(self::$url)) self::urlGenerate();
        //--Retorna Controller referente a URL
        $this->Controller =  $this->selectController();
        //--Renderiza view
        $this->outView = $this->Controller->execute();
    }

    final static protected function urlGenerate() {
        $urlParse = parse_url($_SERVER['REQUEST_URI']);

        $url = isset($urlParse['path']) ? $urlParse['path'] : '/';
        if (substr($url, -1, 1) != '/') $url .= '/';

        if (static::$publicDir === '/') $url = substr($url, 1);
        else $url = substr($url, strlen(static::$publicDir));

        $urlRequired = $url .
            (isset($urlParse['query']) ? '?' . $urlParse['query'] : '') .
            (isset($urlParse['fragment']) ? '#' . $urlParse['fragment'] : '');

        self::$url = [
            self::MODE_URL_REQUIRED => $urlRequired,
            self::MODE_URL_BASE     => $url ?: '',
            self::MODE_URL_EXPLODE  => $url ? explode(' ', trim(str_replace('/', ' ', $url))) : []
        ];
    }

    //--Busca rota predefinida
    final protected function getRouter(): Controller {
        $urlExplode = self::$url[self::MODE_URL_EXPLODE];
        $values = [];
        $Controller = null;

        do {
            //--Busca rota de acordo com a url
            $route = implode('/', $urlExplode);

            //--Se nao existe rota
            if (!isset($this->routes[$route])) {
                if (!count($urlExplode)) break;
                array_unshift($values, array_pop($urlExplode));
                continue;
            }

            //--Pega da rota o controller e a acao
            list($ctrl, $act) = $this->routes[$route];
            $classLoad  = sprintf($this->controllerClassLoad, $ctrl);

            //--Verifica se a classe do controller existe
            if (class_exists($classLoad)) {
                //--Instancia Controller
                /** @var Controller $classLoad */
                $Controller = new $classLoad();
                $Controller->setController($ctrl);

                //--Vericia se o metodo existe para setar a action
                if (!method_exists($Controller, $act . '_')) {
                    //--retorna erro, a action não existe!
                    Application::ErrorReporting()->dispatch("Action {$act} not find in controller {$ctrl}!", ErrorType::SHUTDOWN);
                    exit();
                }
                $Controller->setAction($act);
                $Controller->setValues($values);
                //--Retorna o controller preparado
                return $Controller;
            } else {
                //--Erro!! Rota existe mas a classe não!
                Application::ErrorReporting()->dispatch("Class {$classLoad} not find!", ErrorType::SHUTDOWN);
                exit();
            }
        } while (true);

        $route = implode('/', $urlExplode);
        Application::ErrorReporting()->dispatch("Route \"{$route}\" not find!", ErrorType::SHUTDOWN);
        exit();
    }


    //--Busca rota automaticamente
    final protected function sistemRouter(): Controller {

        //-- Define valores padroes caso não venha parametros na URL
        $urlExplode = self::$url[self::MODE_URL_EXPLODE];
        $controller = null;
        $actions    = [];

        //--Verifica se existe Controller
        while (count($urlExplode)) {

            $auxController = $this->urlToControllerClass($urlExplode);

            //--Verifica se existe o controller
            $classLoad = sprintf($this->controllerClassLoad, $auxController);
            if (class_exists($classLoad)) {
                $controller = $auxController;
                break;
            }

            //--Se nao existe controller, entao adiciona para ser action
            array_unshift($actions, array_pop($urlExplode));
        }

        //--Se nenhum controller encontrado, seta como index
        if (is_null($controller)) {
            $classLoad = sprintf($this->controllerClassLoad, $this->urlToControllerClass(['index']));
            if (class_exists($classLoad)) {
                $controller = 'index';
            } else {
                //--Retorna erro de controller nao encontrado
                Application::ErrorReporting()->dispatch("Controller INDEX not find!", ErrorType::SHUTDOWN);
            }
        }

        //--Instancia Controller
        $Controller = new $classLoad();
        $Controller->setController($controller);

        //--Tratando array de actions
        if (count($actions)) {
            //--seleciona action
            $action  = Strings::toCamelCase(array_shift($actions));
            //--verifica se o metodo da action existe
            if (method_exists($Controller, $action . '_')) $Controller->setAction($action);
            else array_unshift($actions, $action);
        }

        //--Se existe parametros para a função action
        if (count($actions)) $Controller->setValues($actions);

        return $Controller;
    }
}
