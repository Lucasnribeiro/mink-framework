<?php

namespace Mink\Routing;

use Framework\Error\ControllerNotFound;

/**
 * Summary of Router
 */
class Router {
    public static $routes = array();  
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


    /**
     * Summary of __construct
     */
    public function __construct($routes = null){
        $this->routes = $routes;
        $this->buildURI();
    }

    public static function get($path, $resolve, $middleware = null){
        self::$routes['GET'] = array(
            'path'         => $path, 
            'class'        => $resolve[0], 
            'function'     => $resolve[1],
            'method'       => 'GET',
            'middleware'   => $middleware
        );

        return [$path, $resolve];
    }

    public static function post($path, $resolve, $middleware = null){
        self::$routes['POST'] = array(
            'path'          => $path, 
            'class'         => $resolve[0], 
            'function'      => $resolve[1],
            'method'        => 'POST',
            'middleware'    => $middleware
        );

        return [$path, $resolve];
    }

    public static function put($path, $resolve, $middleware = null){
        self::$routes['PUT'] = array(
            'path'          => $path, 
            'class'         => $resolve[0], 
            'function'      => $resolve[1],
            'method'        => 'PUT',
            'middleware'    => $middleware
        );

        return [$path, $resolve];
    }

    public static function patch($path, $resolve, $middleware = null){
        self::$routes['PATCH'] = array(
            'path'          => $path, 
            'class'         => $resolve[0], 
            'function'      => $resolve[1],
            'method'        => 'PATCH',
            'middleware'    => $middleware
        );

        return [$path, $resolve];
    }

    public static function delete($path, $resolve, $middleware = null){
        self::$routes['DELETE'] = array(
            'path'          => $path, 
            'class'         => $resolve[0], 
            'function'      => $resolve[1],
            'method'        => 'DELETE',
            'middleware'    => $middleware
        );

        return [$path, $resolve];
    }

    public function middleware($middleware){
        self::$middleware = new $middleware;
    }

    public static function group($group){
        //resolve the middleware
        self::$middleware = new $group['middleware'];

    }

    private function callController($class, $function, $middleware, $args, $json, $data = null){
        $this->setRequestHeaders();
        
        $request = new Request();

        if($middleware !== null){
            call_user_func([$middleware, 'run'], $request);
        }

        $request->params         = $args;
        $request->querystrings   = $this->querystrings;
        $request->data           = $data;
        $request->json           = json_decode($json, true);

        $controller = new $class;
        
        call_user_func([$controller, $function], $request);

    }

    private function getQueryStrings(){
        if (isset($_SERVER['QUERY_STRING'])){

            parse_str($_SERVER['QUERY_STRING'], $arr);
            return $arr;

        }else{

            return []; 

        }
    }

    public function handlePreflight(){
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $this->setRequestHeaders();
            die(); 
        }
    }

    private function buildURI(){

        $this->request_uri       = $_SERVER['REQUEST_URI'];
        $this->method            = $_SERVER['REQUEST_METHOD'];
        $this->querystrings      = $this->getQueryStrings();
        $removed_querystrings    = preg_replace('/\?.*/', '', $this->request_uri);
        $this->url               = preg_split('@/@', $removed_querystrings, -1, PREG_SPLIT_NO_EMPTY);

        //check if its a preflight request [OPTIONS]
        $this->handlePreflight();
        //try to match the request to it's respective controller
        $this->matchURIwithRoute();

    }

    private function matchURIwithRoute(){

        foreach($this->routes[$this->method] as $route => $declared_route){
            $path = preg_split('@/@', $declared_route['path'], -1, PREG_SPLIT_NO_EMPTY);
            if(sizeof($path) == sizeof($this->url)){
                if($this->url[0] === $path[0]){
                    preg_match_all('!{.*?}+!', $declared_route['path'], $params);
                    $params = preg_replace("/[^a-zA-Z 0-9]+/", "", $params[0] );
                    $this->callController($declared_route['class'], $declared_route['function'], $declared_route['middleware'], $params, file_get_contents('php://input'), $_POST );
                    //controller found and executed, stop routing execution
                    die();
                }
            }
        }

        //if no controller was found
        $this->setRequestHeaders();
        ControllerNotFound::throwError();
    }

    public function setRequestHeaders(){

        $http_origin   = $_SERVER['HTTP_ORIGIN']; 
        $origins       = getenv('ALLOWED_ORIGINS') ? (array) getenv('ALLOWED_ORIGINS') : false;

        if (in_array($http_origin, $origins)){  
            $index = array_search($http_origin, $origins);
            header("Access-Control-Allow-Origin: ". $origins[$index]);
        }

        header("Content-Type: application/json");  
        header('Access-Control-Allow-Credentials: true'); 
        header("Access-Control-Max-Age: 3600");    
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");  
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");  
    }
}