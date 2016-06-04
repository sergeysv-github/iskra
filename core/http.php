<?php

namespace core;

class http
{
    public static $action_raw = NULL;
    public static $params_raw = [];
    public static $page = NULL;
    
    /**
     * Returns a variable passed via GET.
     * 
     * @param string $name Name of the variable.
     * @return mixed Value of the variable, or NULL if not found.
     */
    public static function get($name, $default_value = NULL)
    {
        return (isset($_GET[$name])) ? $_GET[$name] : $default_value;
    }
    
    /**
     * Returns a variable passed via POST.
     * 
     * @param string $name Name of the variable.
     * @return mixed Value of the variable, or NULL if not found.
     */
    public static function post($name)
    {
        return (isset($_POST[$name])) ? $_POST[$name] : NULL;
    }
    
    /**
     * Returns a variable stored in a current session.
     * 
     * @param string $name Name of the variable.
     * @return mixed Value of the variable, or NULL if not found.
     */
    public static function session($name)
    {
        return (isset($_SESSION[$name])) ? $_SESSION[$name] : NULL;
    }
    
    /**
     * Sets a session variable.
     * 
     * @param string $name Name of the variable.
     * @param mixed Value of the variable, or NULL if not found.
     */
    public static function session_set($name, $value)
    {
        $_SESSION[$name] = $value;
    }
    
    /**
     * Unsets a session variable.
     * 
     * @param string $name Name of the variable.
     */
    public static function session_unset($name)
    {
        unset($_SESSION[$name]);
    }
 
    /**
     * Redirects the page to a given URL and terminates the application.
     * 
     * @param string $url URL to be redirected to.
     */
    public static function redirect($url)
    {
        header('Location: '. $url);
        app::halt();
    }    
    
    /**
     * Forces JavaScript to redirect user to another page after AJAX stuff
     * is finished.
     * 
     * @param string $redirect Redirect URL.
     */
    public static function redirect_ajax($redirect) 
    {
        $object = new \stdClass();
        $object->redirect = $redirect;
        // Output JSON-endoded object and terminate the program.
        self::send_ajax_response($object);
    }

    /**
     * Sends data in JSON format and terminates the application.
     * 
     * @param mixed $data Data to be JSON-encoded and sent.
     */
    public static function send_ajax_response($data)
    {
        header('Content-type: application/json');
        echo json_encode($data);
        app::halt();
    }
    
    public static function get_protocol()
    {
        if (!empty($_SERVER)) {
            $https = !empty($_SERVER['HTTPS']) 
                || !empty($_SERVER['HTTP_FRONT_END_HTTPS']);
        } else {
            $https = false;
        }
        return ($https) ? 'https://' : 'http://';
    }
    
    public static function get_hostname()
    {
        if (!empty($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }
        return \models\config::get('app_hostname');
    }
    
    public static function get_baseurl()
    {
        $base_dir  = app::$dirroot;
        $doc_root  = preg_replace("!".$_SERVER['SCRIPT_NAME']."$!", '', $_SERVER['SCRIPT_FILENAME']);
        return preg_replace("!^".$doc_root."!", '', $base_dir);
    }
}
