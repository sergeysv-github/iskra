<?php

namespace modules\admin\views;

use core\html;
use core\nav_button;

class nav extends \core\base_nav
{ 
    public function add_all($query, $attrs = [], $label = '') 
    {
        $this->add(new nav_button(
            html::url($query, $attrs),
            (!empty($label) ? $label : _('All'))
        ), 'left');
    }
    
    public function add_view($query, $attrs, $label = '') 
    {
        $this->add(new nav_button(
            html::url($query, $attrs),
            (!empty($label) ? $label : _('View'))
        ), 'left');
    }
    
    public function add_edit($query, $attrs, $label = '') 
    {
        $this->add(new nav_button(
            html::url($query, $attrs),
            (!empty($label) ? $label : _('Edit'))
        ), 'left');
    }
    
    public function add_delete($query, $attrs, $label = '', $confirm_text = '') 
    {
        if (empty($confirm_text)) {
            $confirm_text = 'Are you sure?';
        }
        $this->add(new nav_button(
            html::url($query, $attrs),
            (!empty($label) ? $label : _('Delete')),
            'btn-danger',
            ['onclick' => "javascript:return confirm('".htmlspecialchars($confirm_text)."');"]
        ), 'right');
    }
    
    public function add_log($query, $attrs, $label = '') 
    {
        $this->add(new nav_button(
            html::url($query, $attrs),
            (!empty($label) ? $label : _('Log'))
        ));
    }
    
    public function add_create($query, $label = '') 
    {
        $this->add(new nav_button(
            html::url($query),
            (!empty($label) ? $label : _('Create')),
            'btn-primary'
        ));
    }
    
    public function render()
    {
        $this->render_template();
    }
}
