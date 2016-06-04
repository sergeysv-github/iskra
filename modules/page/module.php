<?php

namespace modules\page;

use core\app_exception;
use core\html;
use core\http;
use modules\page\models\page;

class module extends \core\base_module
{
    public $default_action = 'home';
	
	public function __construct() 
	{
		$this->admin_menu[_('Pages')] = 'admin/pages';
	}
	
    public static function get_admin_nav($entry = NULL)
    {
        $nav = new \modules\admin\views\nav();
        
        if (empty($entry)) 
        {
//            $nav->add(new \views\navs\nav_view(
//                new \views\search_form()
//            ));
            $nav->add_create('page/create');
        } 
        else 
        {
            $nav->add_all('page/all');
            if (!empty($entry->id)) {
                $nav->add_view('page/view', $entry->id);
                $nav->add_edit('page/edit', $entry->id);
                $nav->add_delete('page/delete', $entry->id);
            }
        }

        return $nav;
    }
	
    public function action_home() 
    {
        return new views\home();
    }
    
    public function action_all() 
    {
        try {
            $count = page::all_filtered_count(func_get_args());
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        $a = $this->page_query();
        try {
            $entries = page::all_filtered($a, func_get_args());
        } catch (app_exception $ex) {
            throw $ex;
        }
		
        return new views\all([
			'nav' => self::get_admin_nav(),
			'count' => $count,
			'entries' => $entries,
		]);
    }
    
    public function action_create()
    {
        //$this->run_ajax_handlers();
        
        $data = http::post('entry');
        if (!empty($data)) {
            $this->entry_create($data);
        }
        
        $entry = new page();
        return new views\create([
			'nav' => self::get_admin_nav(),
			'entry' => $entry
		]);
    }
    
    public function action_edit($id = NULL)
    {
        if (empty($id)) {
            http::redirect(html::url('page/all'));
        }
        
        $data = http::post('entry');
        if (!empty($data)) {
            $this->entry_update($data, $id);
        }
        
        try {
            $entry = page::one(['id' => $id]);
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        if (!$entry) {
            http::redirect(html::url('page/all'));
        }
        
        return new views\edit([
			'nav' => self::get_admin_nav(),
			'entry' => $entry
		]);
    }
	
	private function entry_create($data, $id = null)
	{
        $data = self::array_to_object($data);
        
        $entry = new page();
        if (!$entry->validate($data, 'create')) {
            html::send_ajax_response($entry->errors);
        }
        
        try {
            $id = $entry->save($data);
        } catch (app_exception $ex) {
            html::send_ajax_response($ex->getMessage());
        }
        
        if (!empty($id)) {
//            self::log(
//                sprintf(_('New page created: "%s"'), $entry->name), 
//                page::data_diff($data), 
//                'create',
//                $id
//            );
            html::set_messages(_('New page created.'), html::MSG_SUCCESS);
            http::redirect_ajax(html::url('page/all'));
        } else {
            html::send_ajax_response($entry->errors);
        }
	}
    
    private function entry_update($data, $id)
    {
        $data = self::array_to_object($data);
        $data->id = $id;
        
        $entry = new page();
        if (!$entry->validate($data, 'edit')) {
            html::send_ajax_response($entry->errors);
        }
        
        try {
            $saved = $entry->save($data);
        } catch (app_exception $ex) {
            html::send_ajax_response($ex->getMessage());
        }
        
        if ($saved) {
//            self::log(
//                sprintf(_('New page details saved for #%d.'), $id), 
//                page::data_diff($data), 
//                'edit',
//                $id
//            );
            html::send_ajax_response(_('Details saved.'), TRUE);
        } else {
            html::send_ajax_response($entry->errors);
        }
    }
	
}
