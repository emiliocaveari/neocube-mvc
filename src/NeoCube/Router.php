<?php

namespace NeoCube;

use NeoCube\Error\ErrorType;
use NeoCube\Helper\Strings;
use NeoCube\View\ViewRenderInterface;

class Router {

    const MODE_URL_BASE     = 'url';
    const MODE_URL_REQUIRED = 'urlRequired';
    const MODE_URL_EXPLODE  = 'urlExplode';

    static protected array $url = [];
    static protected string $publicDir = '/';

    protected bool $autoRedirectIndex = false;
    protected Response|ViewRenderInterface $outView;
    protected Controller $Controller;

    protected array $routes = array();
    protected string $controllerClassLoad = '\App\%s\Controller';


    final static public function getUrl(string $mode = self::MODE_URL_REQUIRED): mixed {
        return self::$url[$mode] ?? self::$url[self::MODE_URL_REQUIRED];
    }

    final static public function getPublicUrl(): string {
        return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . static::$publicDir;
    }

    final static public function getPublicDir(): string {
        return static::$publicDir;
    }
    final static public function setPublicDir($public): void {
        if (substr($public, -1, 1) != '/') $public .= '/';
        static::$publicDir = $public;
        self::urlGenerate();
    }


    static public function createLink(null|string $str_router = null, array|bool $get_params = false, bool $merge_params = true) {
        $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . static::$publicDir;

        if ($str_router === null)
            $url .= self::$url[self::MODE_URL_BASE];
        else if (!empty($str_router)) {
            if (substr($str_router, 0, 4) == 'http') {
                $url = $str_router;
            } else {
                $str_router = implode('/', array_filter(explode('/', $str_router)));
                $url .= $str_router;
            }
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


    static public function redirect(array|string $controller, string|null $action = null, array|string|null $params = null): void {
        if (php_sapi_name() == 'cli') return;
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


    final public function setControllerClassLoad(string $controllerClassLoad): void {
        $this->controllerClassLoad = $controllerClassLoad;
    }
    final public function getControllerClassLoad(): string {
        return $this->controllerClassLoad;
    }
    final public function getController(): ?Controller {
        return $this->Controller;
    }


    protected function selectController(): Controller {
        return $this->sistemRouter();
    }

    protected function urlToControllerClass(array $url): string {
        return Strings::toCamelCase(implode('\\', $url));
    }


    final public function writeOut(): void {
        if ($this->outView instanceof ViewRenderInterface) $this->outView->render();
        else if ($this->outView instanceof Response) $this->outView->execute();
    }

    final public function getOut(): ViewRenderInterface|Response {
        return $this->outView;
    }

    final public function routing(?string $forceUrl = null): void {
        if (empty(self::$url) or $forceUrl) self::urlGenerate($forceUrl);
        $this->Controller =  $this->selectController();
        $this->outView = $this->Controller->execute();
    }

    final static protected function urlGenerate(?string $forceUrl = null): void {
        $urlParse = $forceUrl
            ? parse_url($forceUrl)
            : (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI']) : '');

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

    
    final protected function sistemRouter(): Controller {

        $urlExplode = self::$url[self::MODE_URL_EXPLODE] ?: ['index'];
        $controller = null;
        $actions    = [];

        while (count($urlExplode)) {
            $auxController = $this->urlToControllerClass($urlExplode);
            $classLoad = sprintf($this->controllerClassLoad, $auxController);
            if (class_exists($classLoad)) {
                $controller = $auxController;
                break;
            }
            array_unshift($actions, array_pop($urlExplode));
        }

        if (is_null($controller)) {

            if (!$this->autoRedirectIndex)
                Application::ErrorReporting()->dispatch("Controller not find to router!", ErrorType::SHUTDOWN);

            $classLoad = sprintf($this->controllerClassLoad, $this->urlToControllerClass(['Index']));
            if (class_exists($classLoad)) {
                $controller = 'Index';
            } else {
                Application::ErrorReporting()->dispatch("Controller INDEX not find!", ErrorType::SHUTDOWN);
            }
        }

        $Controller = new $classLoad();
        $Controller->setController($controller);

        if (count($actions)) {
            $action  = Strings::toCamelCase(array_shift($actions));
            if (method_exists($Controller, $action . '_')) $Controller->setAction($action);
            else array_unshift($actions, $action);
        }

        if (count($actions)) $Controller->setValues($actions);

        return $Controller;
    }
}
