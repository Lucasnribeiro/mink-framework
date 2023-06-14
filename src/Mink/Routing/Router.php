<?php

namespace Mink\Routing;

use Mink\Error\ControllerNotFound;

/**
 * Summary of Router
 */
class Router {
    public static $routes = [];  
    /**
        * Key value array of querystrings 
        * @var array
    */
    private $querystrings;
    /**
        * A single class or an array of classes 
        * @var mixed 
    */
    private $middleware;
    /**
        * String containing the complete uri from this request
        * @var array
    */
    private $request_uri;
    /**
        * String containing the declared route path of this request
        * @var array
    */
    private $path;
    /**
        * String containing the reuqest url without the querystrings
        * Used to match against the declared route path
        * @var array
    */
    private $url;
    private $method;


    public function __construct() {
        $this->buildURI();
    }

    public static function addRoute($method, $path, $resolve, $middleware = null) {
        self::$routes[] = [
            'method'     => $method,
            'path'       => $path,
            'class'      => $resolve[0],
            'function'   => $resolve[1],
            'middleware' => $middleware,
        ];
    }

    public static function get($path, $resolve, $middleware = null) {
        self::addRoute('GET', $path, $resolve, $middleware);
    }

    public static function post($path, $resolve, $middleware = null) {
        self::addRoute('POST', $path, $resolve, $middleware);
    }

    public static function put($path, $resolve, $middleware = null) {
        self::addRoute('PUT', $path, $resolve, $middleware);
    }

    public static function patch($path, $resolve, $middleware = null) {
        self::addRoute('PATCH', $path, $resolve, $middleware);
    }

    public static function delete($path, $resolve, $middleware = null) {
        self::addRoute('DELETE', $path, $resolve, $middleware);
    }

    public function middleware($middleware) {

    }

    public static function group($group) {

    }

    private function callController($class, $function, $middleware, $args, $json, $data = null) {
        $this->setRequestHeaders();
        
        $request = new Request();

        if ($middleware !== null) {
            call_user_func([$middleware, 'run'], $request);
        }

        $request->params = $args;
        $request->querystrings = $this->getQueryStrings();
        $request->data = $data;
        $request->json = json_decode($json, true);

        $controller = new $class();
        
        call_user_func([$controller, $function], $request);
    }

    private function getQueryStrings() {
        if (array_key_exists('QUERY_STRING', $_SERVER)){
            parse_str($_SERVER['QUERY_STRING'], $arr);
            return $arr;
        }else{
            return [];
        }
    }

    public function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $this->setRequestHeaders();
            die(); 
        }
    }

    private function buildURI() {
        $this->request_uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->url = parse_url($this->request_uri, PHP_URL_PATH);
        
        // Check if it's a preflight request [OPTIONS]
        $this->handlePreflight();
        // Try to match the request to its respective controller
        $this->matchURIwithRoute();
    }

    private function matchURIwithRoute() {
        foreach (self::$routes as $route) {
            if ($route['method'] == $this->method && $route['path'] == $this->url) {
                $this->callController($route['class'], $route['function'], $route['middleware'], [], file_get_contents('php://input'), $_POST);
                // Controller found and executed, stop routing execution

                die();
            }
        }

        // If no controller was found
        $this->setRequestHeaders();
        ControllerNotFound::throwError();
    }

    public function setRequestHeaders() {

        if (array_key_exists('HTTP_ORIGIN', $_SERVER)){

            $http_origin = $_SERVER['HTTP_ORIGIN']; 
            $origins = getenv('ALLOWED_ORIGINS') ? (array) getenv('ALLOWED_ORIGINS') : [];

            if (in_array($http_origin, $origins)) {  
                $index = array_search($http_origin, $origins);
                header("Access-Control-Allow-Origin: " . $origins[$index]);
            }

            header("Content-Type: application/json");  
            header('Access-Control-Allow-Credentials: true'); 
            header("Access-Control-Max-Age: 3600");    
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");  
            header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");  
        }

    }
}