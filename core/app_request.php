<?php

namespace core;

class app_request
{
    /**
     * Contains the name of the requested action of a requested controller.
     * Derived from $this->action_raw.
     * @var string
     */
    public $action;
    
    /**
     * Raw string containing the requested query.
     * Stored in $_GET['q'].
     * Consists of controller and action, separated by forward slash
     * (e.g. "users/all").
     * @var string 
     */
    public $query;
    
    /**
     * Contains the name of the requested controller.
     * Derived from $this->action_raw.
     * @var string
     */
    public $controller;
    
    /**
     * Contains the property that the results should be ordered by.
     * Stored in $_GET['ob'].
     * @var string 
     */
    public $order_by;
    
    /**
     * Contains the direction (ascending/descending) that the results should be 
     * ordered by.
     * Stored in $_GET['od'].
     * Possible values: "asc" or "desc".
     * @var string 
     */
    public $order_direction;
    
    /**
     * Contains the number of the requested page. Starts with zero.
     * Stored in $_GET['p'].
     * @var int 
     */
    public $page;
    
    /**
     * Contains all arguments that are to be passed into the requested
     * controller. Derived from $this->args_raw.
     * @var array
     */
    public $args = [];
    
    /**
     * Raw string containing the requested action arguments. 
     * Stored in $_GET['a'].
     * Consists of string values, separated by forward slash.
     * @var string 
     */
    public $args_raw;
    
    /**
     * Raw URL that has been requested.
     * @var string 
     */
    public $url;
}
