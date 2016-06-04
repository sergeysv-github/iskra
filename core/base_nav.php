<?php

namespace core;

abstract class base_nav extends base_view 
{
    public $menu = [];
    
    /**
     * Adds a menu item to the menu.
     * 
     * @param \views\menu\menu_item $item Menu item object.
     * @param string $section Menu section.
     * @return void
     */
    public function add($item, $section = 'left')
    {
        // Check access.
        if (empty($item->url) || app::get_user()->has_access_to($item->controller.'/'.$item->action)) {
            $this->menu[$section][] = $item;
        }
    }
    
}

class nav_button
{
    public $url;
    public $controller;
    public $action;
    public $label;
    public $classes;
    public $attrs;

    /**
     * Builds new menu item.
     * 
     * @param string $url URL (either controller/action pair or fully-formed)
     * @param string $label Label
     * @param string $classes Additional HTML classes for the link.
     * @param array $attrs Additional HTML attributes for the link.
     */
    public function __construct($url, $label, $classes = '', $attrs = [])
    {
        $this->url = (!empty($url)) ? html::url($url) : NULL;
        $this->label = $label;
        $this->classes = $classes;
        $this->attrs = $attrs;
        $this->controller = (!empty($url)) ? html::url_query_part($this->url, 'controller') : NULL;
        $this->action = (!empty($url)) ? html::url_query_part($this->url, 'action') : NULL;
    }
}

class nav_view
{
    public $view;
    
    public function __construct($view = NULL, $action = '')
    {
        if (!empty($view) && is_subclass_of($view, '\core\base_view')) {
            if (empty($action) || app::$user->has_access_to($action)) {
                $this->view = $view;
            }
        }
    }
}
