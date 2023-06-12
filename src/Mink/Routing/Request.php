<?php 

namespace Mink\Routing;


class Request {


    public $params          = array();
    public $querystrings    = array();
    public $data            = array();
    public $user            = array();
    public $json            = array();
}