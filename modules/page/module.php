<?php

namespace modules\page;

use core\http;
use modules\page\models\page;

class module extends \core\base_module
{
    public $default_action = 'home';
	
	public function __construct() 
	{
		$this->admin_menu[_('Pages')] = 'admin/pages';
	}
	
    public function action_home() 
    {
        return new views\home();
    }
    
    public function action_all() 
    {
        return new views\all();
    }
    
    public function action_create()
    {
        //$this->run_ajax_handlers();
        
        $data = http::post('entry');
        if (!empty($data)) {
            $this->entry_insert($data);
        }
        
        $entry = new page();
        return new views\create([
			'entry' => $entry
		]);
    }
	
}
