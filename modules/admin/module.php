<?php

namespace modules\admin;

class module extends \core\base_module
{
    public $default_action = 'home';
    
    public function action_home() 
    {
        return new views\home();
    }
}
