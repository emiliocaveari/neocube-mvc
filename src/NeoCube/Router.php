<?php

namespace NeoCube;

use NeoCube\Error\ErrorType;
use NeoCube\Helper\Strings;
use NeoCube\Http\ContextStorage;
use NeoCube\Http\RequestTerminated;
use NeoCube\View\ViewRenderInterface;

class Router {

    const MODE_URL_BASE     = 'url';
    const MODE_URL_REQUIRED = 'urlRequired';
    const MODE_URL_EXPLODE  = 'urlExplode';

    protected array $url = [];
    protected string $publicDir = '/';

    protected bool $autoRedirectIndex = false;
    protected Response|ViewRenderInterface $outView;
    protected Controller $Controller;

    protected array $routes = array();
    protected string $controllerClassLoad = '\App\%s\Controller';


    final static public function getUrl(string $mode = self::MODE_URL_REQUIRED): mixed {
        $router = Application::Router();
        if (!$router) return null;
        return $router->url[$mode] ?? $router->url[self::MODE_URL_REQUIRED] ?? null;
    }

    final static public function getPublicUrl(): string {
        $server = ContextStorage::current()->server;
        $publicDir = Application::Router()?->publicDir ?? '/';
        return (isset($server['HTTPS']) ? 'https://' : 'http://') . ($server['HTTP_HOST'] ?? '') . $publicDir;
    }

    final static public function getPublicDir(): string {
        return Application::Router()?->publicDir ?? '/';
    }
    final public function setPublicDir(string $public): void {
        if (substr($public, -1, 1) != '/') $public .= '/';
        $this->publicDir = $public;
    }


    static public function createLink(null|string $str_router = null, ?array $params = null) {
        $router = Application::Router();
        $publicDir = $router?->publicDir ?? '/';

        $server = ContextStorage::current()->server;
        $url = (isset($server['HTTPS']) ? 'https://' : 'http://') . ($server['HTTP_HOST'] ?? '') . $publicDir;

        if ($str_router === null)
            $url .= $router?->url[self::MODE_URL_BASE] ?? '';
        else if (!empty($str_router)) {
            if (substr($str_router, 0, 4) == 'http') {
                $url = $str_router;
            } else {
                $str_router = implode('/', array_filter(explode('/', $str_router)));
                $url .= $str_router;
            }
        }

        if (substr($url, -1, 1) == '/') $url = substr($url, 0, -1);

        if ($params)
            $url .= '?' . http_build_query($params);

        return $url;
    }


    static public function redirect(array|string $controller, string|null $action = null, array|string|null $params = null): void {
        if (PHP_SAPI === 'cli' && !defined('NEOCUBE_HTTP_CONTEXT')) return;
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
            $location = (Application::Router()?->publicDir ?? '/') . $controller . $action . $params;
        }

        $ctx = ContextStorage::current();
        $ctx->addResponseHeader("location: $location");
        $ctx->setStatus(302);
        throw new RequestTerminated();
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
        if (empty($this->url) or $forceUrl) $this->urlGenerate($forceUrl);
        $this->Controller =  $this->selectController();
        $this->outView = $this->Controller->execute();
    }

    final protected function urlGenerate(?string $forceUrl = null): void {
        $server = ContextStorage::current()->server;
        $urlParse = $forceUrl
            ? parse_url($forceUrl)
            : (isset($server['REQUEST_URI']) ? parse_url($server['REQUEST_URI']) : '');

        $url = isset($urlParse['path']) ? $urlParse['path'] : '/';
        if (substr($url, -1, 1) != '/') $url .= '/';

        if ($this->publicDir === '/') $url = substr($url, 1);
        else $url = substr($url, strlen($this->publicDir));

        $urlRequired = $url .
            (isset($urlParse['query']) ? '?' . $urlParse['query'] : '') .
            (isset($urlParse['fragment']) ? '#' . $urlParse['fragment'] : '');

        $this->url = [
            self::MODE_URL_REQUIRED => $urlRequired,
            self::MODE_URL_BASE     => $url ?: '',
            self::MODE_URL_EXPLODE  => $url ? array_filter(explode('/', trim("$url"))) : []
        ];
    }


    final protected function sistemRouter(): Controller {

        $urlExplode = $this->url[self::MODE_URL_EXPLODE] ?: ['index'];
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
