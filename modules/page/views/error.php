<?php

namespace modules\page\views;

class error extends \core\base_view
{
    public $layout = 'login';
    
    private function get_backtrace_html()
    {
        if (empty($this->backtrace)) {
            return '';
        }
        
        ob_start();
        
        echo '<p>'.__('Debug backtrace').':</p>';
        echo '<ol>';
        foreach ($this->backtrace as $line) {
            echo '<li>'. $line .'</li>';
        }
        echo '</ol>';
        
        return ob_get_clean();
    }
    
    public function render()
    {
        $this->backtrace = $this->get_backtrace_html();
        $this->render_template();
    }
}