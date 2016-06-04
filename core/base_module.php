<?php

namespace core;

abstract class base_module
{
    public $admin_menu = [];
    public $default_action;
    public $page;
    
    protected $limit = 25;
    protected $order_by_default = 'id';
    protected $order_dir_default = 'ASC';
    
    public function set_page($value) 
    {
        $this->page = (int) $value;
    }
    
    /**
     * Returns a DB argument class to obtain exactly one page of the results,
     * ordered properly.
     * 
     * @return \core\db_arg DB argument.
     */
    protected function page_query()
    {
        $arg = new db_arg();
        $arg->offset = $this->page * $this->limit;
        $arg->limit = $this->limit;
        if (!empty(app::$request->order_by)) {
            $arg->order_by = app::$request->order_by;
        } else {
            $arg->order_by = $this->order_by_default;
        }
        if (!empty(app::$request->order_direction)) {
            $arg->order_dir = app::$request->order_direction;
        } else {
            $arg->order_dir = $this->order_dir_default;
        }
        return $arg;
    }
    
    /**
     * Writes a new message into the system log.
     * 
     * @param string $message Message to be recorded.
     * @param string $details More data related to the action.
     * @param string $action Action taken (usually the method name, default: 
     * @param int $instance_id ID of the instance affected by the action.
     * empty).
     * @param int $level Log entry level. Acceptable values are:
     * <ul>
     * <li>\models\log::LEVEL_INFO (default)</li>
     * <li>\models\log::LEVEL_WARNING</li>
     * <li>\models\log::LEVEL_ERROR</li>
     * </ul>
     * @param int $user_id ID of the user who performed the action.
     * @return mixed The new log entry ID, or FALSE on failure.
     */
    public static function log($message, $details = '', $action = NULL, $instance_id = NULL, $level = \models\log::LEVEL_INFO, $user_id = 0) {
        $controller = explode('\\', get_called_class());
        return \models\log::write($message, $details, $level, array_pop($controller), $action, $user_id, $instance_id);
    }
    
    public static function array_to_object($array) {
        return json_decode(json_encode($array), FALSE);
    }
    
    /**
     * Handles the data export into formats other than HTML.
     * Outputs the file content and terminates the application.
     * 
     * @param array $config Configuration array. Possible values:
     * <ul>
     * <li>"format" - Any of the export formats available ("csv", "pdf", etc.)</li>
     * <li>"filename" - File name to be used during download.</li>
     * </ul>
     * @param array $data Data to be exported.
     */
    protected function export($config, $data)
    {
        if (empty($data)) {
            app::halt();
        }
        
        $format = $config['format'];
        if ($format != 'csv') { // TODO smth more flexible
            app::halt();
        }
        
        $renderer = new \controllers\renderers\csv($data);
        $renderer->filename = $config['filename'];
        $renderer->download();
    }
}
