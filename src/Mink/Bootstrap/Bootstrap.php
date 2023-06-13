<?php
namespace Mink\Bootstrap;
use Mink\Routing\Router;

class Bootstrap {

    public function __construct($routes){
        $this->start($routes);
    }

    private function start($routes){

        // start the application
        $router = new Router($routes);
    }
}

