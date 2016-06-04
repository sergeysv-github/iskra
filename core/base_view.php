<?php

namespace core;

abstract class base_view 
{
    public $controller_nav = '';
    public $layout = '\\modules\\page\\layouts\\iskra\\generic';
    public $nav_instance = '';
    public $side_nav = '';
    public $title;
    
    /**
     * If an associated array of values passed here, public properties will be
     * created, named after the array's keys and filled with array's values.
     * 
     * These values will be available inside this view's templates. For example,
     * if $data contains a property named "title", it will be available inside
     * this view's template as $this->title.
     * 
     * @param array $data Array of values (empty by default).
     * @return void
     */
    public function __construct($data = array()) {
        if (empty($data)) {
            return;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Allows views to be displayed directly, e.g.:
     * <code>
     * echo new \views\menu\main_menu();
     * echo $menu_object;
     * </code>
     * 
     * @return string View's HTML code.
     */
    public function __toString() 
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
    
    /**
     * Returns the list of all public and non-static properties of this view.
     * 
     * @return array Array of the properties.
     */
    public function attributes()
    {
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }
        return $names;
    }
    
    /**
     * Extract all public properties from the view (to be used in a layout).
     * 
     * @return array Array of data (ready to use by a view constructor)
     */
    public function get_data()
    {
        $data = [];
        foreach ($this->attributes() as $name) {
            $data[$name] = (!empty($this->$name)) ? $this->$name : NULL;
        }
        return $data;
    }
    
    /** 
     * To be used if some actions need to be performed by the view BEFORE
     * it is rendered.
     */
    public function pre_render() {}
    
    /**
     * Outputs the contents of the view.
     */
    public function render() {}
    
    /**
     * Includes a JavaScript file into a document. If only a file name is
     * specified in the path, a standard layout path will be used.
     * 
     * @param string $script Script file name, optionally with a path.
     * @return string HTML <script> tag.
     */
    public function load_js($script)
    {
        if (substr($script, 0, 1) == '/' || substr($script, 0, 4) == 'http') {
            $path = $script;
        } else {
            $path = '/views/layouts/tpl/js/'. $script;
        }
        echo '<script src="'.$path.'"></script>';
    }
    
    public function load_wysiwyg_editor()
    {
        $this->load_js('jquery.hotkeys.js');
        $this->load_js('bootstrap-wysiwyg.js');
        $this->load_js('app-wysiwyg.js');
    }
    
    public function load_datepicker()
    {
        $this->load_css('bootstrap-datetimepicker.css');
        $this->load_js('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/moment.min.js');
        $this->load_js('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/locale/en-au.js');
        $this->load_js('bootstrap-datetimepicker.js');
    }
    
    public function load_file_uploader()
    {
        $this->load_css('jquery.fileupload.css');
        
        $this->load_js('jquery.ui.widget.js');
        $this->load_js('jquery.iframe-transport.js');
        $this->load_js('jquery.fileupload.js');
        $this->load_js('fileupload.js');
    }
    
    public function load_dynamic_select()
    {
        $this->load_css('selectize.css');
        $this->load_js('selectize.js');
    }
    
    public function load_advanced_search_form()
    {
        $this->load_dynamic_select();
        $this->load_css('search-form-advanced.css');
        $this->load_js('search-form-advanced.js');
    }
    
    /**
     * Includes a CSS file into a document. If only a file name is
     * specified in the path, a standard layout path will be used.
     * 
     * @param string $css CSS file name, optionally with a path.
     * @return string HTML <link> tag.
     */
    public function load_css($css)
    {
        if (substr($css, 0, 1) == '/') {
            $path = $css;
        } else {
            $path = '/views/layouts/tpl/css/'. $css;
        }
        echo '<link rel="stylesheet" href="'.$path.'">';
    }
    
    /**
     * Outputs the template file via include().
     * 
     * @param type $template_name Template name. If empty, the view class name is used.
     * @param type $view_classname View classname with the namespace.
     * Current view used by default.
     */
    public function render_template($template_name = '', $view_classname = NULL)
    {
        if (empty($view_classname)) {
            $view_classname = get_class($this);
            $x = explode('\\', $view_classname);
            $view_name = array_pop($x);
            $view_classname = implode('/', $x);
        }
        
        if (empty($template_name)) {
            $template_name = $view_name;
        }
        
        $path = app::$dirroot .'/'. $view_classname . '/templates/' .$template_name .'.php';
        if (file_exists($path)) {
            include $path;
        }
    }
}
