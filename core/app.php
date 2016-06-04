<?php

namespace core;

class app
{
    public static $db;
    public static $dirroot;
    public static $messages;
    public static $request;
    public static $user;
    
    public function __construct(\PDO &$db) {
        self::$db = $db;
        self::$dirroot = dirname(dirname(__FILE__));
        self::$messages = http::session('quix_messages');
        self::$user = new \modules\user\models\user();
        self::$request = new app_request();
    }
    
    /**
     * Attempts to create a PDO object based on the config parameters.
     * 
     * @param object $config Configuration object (DB host, DB name, DB user,
     * DB password).
     * @return \PDO PDO class instance.
     * @throws app_exception
     */
    public static function load_db($config)
    {
        try {
            $pdo = new \PDO(
                'mysql:dbname=' . $config->dbname . ';host=' . $config->host, 
                $config->username, 
                $config->password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                ]
            );
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        return $pdo;
    }
    
    /**
     * Parses the GET request and finds out what is an action required,
     * and what is other parameters.
     * 
     * Only certain GET variables are accepted (see \core\http_request
     * documentation). All other variables will be ignored.
     * 
     * @returns \core\http_request Request object.
     */
    public static function parse_http_request()
    {
        $request = new app_request();
        
        // Get full URL.
        if (!empty($_SERVER)) {
            $request->url = http::get_protocol() . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }
        
        // Get query.
        $query_raw = http::get('q');
        if (!empty($query_raw)) {
            $query = explode('/', $query_raw);
            $request->controller = (!empty($query[0])) ? $query[0] : null;
            $request->action = (!empty($query[1])) ? $query[1] : null;
        }
        $request->query = rawurldecode($query_raw);
        
        // Get variables.
        $request->args_raw = http::get('a');
        $request->args = http::get('a');
        
        // Get page number.
        $request->page = http::get('p');
        
        // Get order by.
        $request->order_by = http::get('ob');
        
        // Get order direction.
        //$request->order_direction = (http::get('od') == 'desc') ? 'desc' : 'asc';
        $request->order_direction = http::get('od');
        
        // Save the ordering for the given controller into session.
        if (!empty($request->order_by)) {
            $list_orderings = http::session('list_orderings');
            $list_orderings[$query_raw] = [$request->order_by, $request->order_direction];
            http::session_set('list_orderings', $list_orderings);
        }
        
        return $request;
    }
    
    /**
     * Run the system cron via CLI.
     * POST, GET and SESSION variables are not available here!
     */
    public function run_cron()
    {
        if (php_sapi_name() !== 'cli') {
            return;
        }
        
        $cron = new \controllers\cron\cron();
        $cron->run();
    }
    
    /**
     * Here's where all the magic happens.
     * Controller and action are determined here, and a view is called
     * and rendered if needed.
     */
    public function run()
    {
        // Find out what's requested.
        self::$request = self::parse_http_request();
        
        // Load the controller. If not found, default controller is used.
        try {
            $controller = $this->load_controller(self::$request->controller);
            if (!$controller) {
                $controller = $this->load_default_controller();
            }
        } catch (\Exception $e) {
            throw new app_exception(_('Invalid HTTP request.'));
        }
        
        // Define the action requested.
        $action = self::$request->action;
        if (empty($action)) {
            $action = $controller->default_action;
        } 
        
        if (!method_exists($controller, 'action_'.$action)) {
            // TODO or use a default action?
            throw new app_exception(_('Invalid HTTP request.'));
        }
        
        // If we have gotten this far, populate the current user's data.
        $userid = self::get_user()->logged_in_id();
        if (!empty($userid)) {
            try {
                self::$user = \modules\user\models\user::one(['id' => $userid]);
            } catch (app_exception $ex) {
                throw $ex;
            }
        }
        
        // Check if user is capable of performing the action requested.
        if (!$this->action_is_allowed(self::$request->controller, $action)) {
            $this->show_fatal_error('Access denied.<br><small>Your user does not have the permissions required to access this function in Quixotic. If you are getting this error on the home page you probably don\'t have any roles assigned and should contact the Androgogic Service Desk on 1800 987 757.</small>');
        }
        
        // Set the page-related values of the request.
        $controller->set_page(self::$request->page);
        
        // Execute the action and load the view (if exists).
        // The script can terminate here, because some controllers can use
        // redirects or other kinds of actions that require interruption of
        // the further workflow (such as AJAX output).
        try {
            $view = call_user_func_array(
                [$controller, 'action_'.$action], 
                [self::$request->args]
            );
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        // Render the page using the required view.
        // This is the first time that any output begins, unless something
        // to that effect was done by the controller's action earlier.
        $this->render_view($view);
    }
    
    private function action_is_allowed($controller, $action)
    {
		return true; // FIXME
		
        if (empty($controller)) {
            // Redirect to home page.
            http::redirect(html::url('page/home'));
        }
        if (in_array($controller, ['api', 'api_legacy']) && $action == 'call') {
            // Access allowed to the login page.
            return TRUE;
        } elseif ($controller == 'auth' && in_array($action, ['login', 'reset', 'new_password'])) {
            // Access allowed to API (it uses its own authentication mechanism, if needed)
            return TRUE;
        } elseif ($controller == 'users' && in_array($action, ['logoutas'])) {
            // Logging back in as yourself.
            return TRUE;
        } elseif (!self::get_user()->is_logged_in()) {
            // Remember the page we wanted to visit, then redirect to login.
            http::session_set('original_request_url', $_SERVER['REQUEST_URI']);
            http::redirect(html::url('auth/login'));
        }
        return self::get_user()->has_access_to($controller.'/'.$action);
    }
    
    /**
     * Renders the page using the required view.
     * 
     * @param view $view View object.
     * @throws app_exception
     */
    public function render_view(&$view)
    {
        // Nothing to output?
        if (empty($view)) {
            return;
        }
        
        // View may do something BEFORE the output begins.
        $view->pre_render();
        
        // Find and initialize the layout.
        $layout_class = '\\modules\\page\\layouts\\iskra\\'.$view->layout;
        if (!class_exists($layout_class)) {
            throw new app_exception(__('Invalid layout.'));
        }
        $layout = new $layout_class($view->get_data());
        
        // Pass the view into the layout.
        $layout->view = $view;
        
        // Render the layout.
        $layout->render();
    }
    
    /**
     * Loads the default controller for the site. Currently - home page.
     * 
     * @return \controllers\page
     */
    private function load_default_controller()
    {
        return new \modules\page\module();
    }
    
    /**
     * Loads the controller based on its class name.
     * 
     * @param string $class Class name.
     * @return mixed Returns an instance of the class, or FALSE on failure.
     */
    public function load_controller($class)
    {
        if (empty($class)) {
            return FALSE;
        }
        
        $fullname = '\\controllers\\'.$class.'\\'.$class;
        try {
            if (!class_exists($fullname)) {
                return FALSE;
            }
        } catch (\Exception $e) {
            throw $e;
        }
        
        return new $fullname();
    }

    /**
     * Returns the PDO database connector.
     * 
     * @return \core\database
     */
    public static function get_db()
    {
        return self::$db;
    }

    /**
     * Returns the user currently logged in.
     * 
     * @return \models\user
     */
    public static function get_user()
    {
        return self::$user;
    }
    
    public static function halt($message = '')
    {
        die($message);
    }

    /**
     * Builds a tree of all available controllers and actions.
     * 
     * @return array Tree of controllers and their actions.
     */
    public static function get_all_actions()
    {
        $actions = [];
        
        $controllers = scandir(self::$dirroot.'/controllers');
        foreach ($controllers as $controller) {
            if (in_array($controller, ['.', '..', '_new_controller'])) {
                continue;
            }
            try {
                $controller_full = '\\controllers\\'.$controller.'\\'.$controller;
                if (!class_exists($controller_full)) {
                    continue;
                }
            } catch (\LogicException $ex) {
                continue;
            }
            $methods = get_class_methods($controller_full);
            foreach ($methods as $method) {
                if (substr($method, 0, 7) !== 'action_') {
                    continue;
                }
                $actions[$controller][] = str_replace('action_', '', $method);
            }
        }
        
        return $actions;
    }
    
    public static function get_all_actions_list()
    {
        $actions = self::get_all_actions();
        $actions_flat = [];
        foreach ($actions as $controller => $methods) {
            foreach ($methods as $method) {
                $actions_flat[] = $controller.'/'.$method;
            }
        }
        return $actions_flat;
    }
    
    /**
     * Display the fatal error page and terminates the program.
     * 
     * @param string $message Error message to display.
     * @param string $details Additional details. Default: empty.
     */
    public function show_fatal_error($message, $details = NULL)
    {
        $view = new \modules\page\views\error([
            'message' => $message,
            'backtrace' => $details,
        ]);
        $this->render_view($view);
        self::halt();
    }
    
}
