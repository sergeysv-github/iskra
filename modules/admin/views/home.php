<?php

namespace modules\admin\views;

class home extends \core\base_view
{
	public $layout = '\\modules\\admin\\layouts\\iskra\\admin';
	
    public function render()
    {
        $this->render_template();
    }
}
