<?php

namespace modules\page\views;

class create extends \core\base_view
{
	public $layout = '\\modules\\admin\\layouts\\iskra\\admin';
	
    public function render()
    {
        $this->render_template('edit');
    }
}
