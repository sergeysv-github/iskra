<?php

namespace core;

class html
{
    const MSG_SUCCESS = 1;
    const MSG_NOTICE = 2;
    const MSG_WARNING = 3;
    const MSG_ERROR = 4;
    
    const DATE_LOG = 'd/m/Y, H:i:s';
    const DATE_TICKET = 'j M Y, H:i';
    
    /**
     * Returns a relative application URL.
     * 
     * @param string $query Query ("controller/action" pair, or a fully-formed
     * URL). Default: current.
     * @param mixed $args Argument or an array of arguments for the action 
     * (default: none).
     * @param int $page Current page (default: empty)
     * @param string $order_by Ordering parameter (default: empty)
     * @param string $order_dir Ordering direction (default: empty)
     * @return string Resulting URL.
     */
    public static function url($query = NULL, $args = [], $page = NULL, $order_by = NULL, $order_dir = NULL)
    {
        if (empty($query)) {
            $query = app::$request->query;
        } elseif (self::is_full_url($query)) {
            return $query;
        }
        
        $url = 'index.php?q='.$query;
        if (!empty($args)) {
            if (is_array($args)) {
                foreach ($args as $key => $value) {
                    $url .= '&a['.$key.']='. rawurlencode($value);
                }
            } else {
                $url .= '&a='. $args;
            }
        }
        if (!empty($page)) {
            $url .= '&p='. $page;
        }
        if (!empty($order_by)) {
            $url .= '&ob='. $order_by;
        }
        if (!empty($order_dir)) {
            $url .= '&od='. $order_dir;
        }
        return $url;
    }
    
    /**
     * Returns an absolute application URL.
     * 
     * @param string $query Query ("controller/action" pair, or a fully-formed
     * URL). Default: current.
     * @param mixed $args Argument or an array of arguments for the action 
     * (default: none).
     * @param int $page Current page (default: empty)
     * @param string $order_by Ordering parameter (default: empty)
     * @param string $order_dir Ordering direction (default: empty)
     * @return string Resulting URL.
     */
    public static function url_full($query = NULL, $args = [], $page = NULL, $order_by = NULL, $order_dir = NULL)
    {
        $url  = http::get_protocol();
        $url .= http::get_hostname();
        $url .= '/';
        $url .= self::url($query, $args, $page, $order_by, $order_dir);
        return $url;
    }
    
    /**
     * Returns the requested controller or action from the given URL.
     * 
     * @param string $url URL to be parsed.
     * @param string $part Required part ("controller" or "action").
     * @return string Controller, or NULL if not found.
     */
    public static function url_query_part($url, $part = 'controller')
    {
        // Check if there IS a query.
        $url_query = parse_url($url, PHP_URL_QUERY);
        if (empty($url_query)) {
            return NULL;
        }
        
        // Check if there's a "q" variable (controller/action pair).
        parse_str($url_query);
        if (empty($q)) {
            return NULL;
        }
        
        $query = explode('/', $q);
        if ($part == 'controller') {
            return (!empty($query[0])) ? $query[0] : NULL;
        }
        if ($part == 'action') {
            return (!empty($query[1])) ? $query[1] : NULL;
        }
        
        return NULL;
    }
    
    /**
     * Renders a URL for the view reordering.
     * 
     * @param string $order_by Parameter to order the view by.
     * @return object Resulting URL
     */
    public static function url_orderby($order_by)
    {
        $order_dir = app::$request->order_direction;
        if ($order_by == app::$request->order_by) {
            $order_dir = (app::$request->order_direction == 'DESC') ? 'ASC' : 'DESC';
        }
        return self::url(
            app::$request->query,
            app::$request->args,
            app::$request->page,
            $order_by,
            $order_dir
        );
    }
    
    public static function is_full_url($url)
    {
        // Should start with http://, https://, or forward slash.
        return (preg_match('/^(https?:\/\/|\/)/', $url) || strpos($url, 'index.php') !== false);
    }
    
    /**
     * Saves a message to be displayed later for user.
     * 
     * @param mixed $message Message, or array of messages.
     * @param int $level Message level. Acceptable values are:
     * <ul>
     * <li>html::MSG_SUCCESS</li>
     * <li>html::MSG_NOTICE</li>
     * <li>html::MSG_WARNING</li>
     * <li>html::MSG_ERROR</li>
     * </ul>
     */
    public static function set_messages($message, $level)
    {
        $messages = http::session('quixotic_messages');
        if (is_array($message)) {
            foreach ($message as $msg) {
                $messages[$level][] = $msg;
            }
        } else {
            $messages[$level][] = $message;
        }
        http::session_set('quixotic_messages', $messages);
    }
    
    /**
     * Returns all messages saved for the user at this stage, and erases them
     * from session data.
     * 
     * @return string HTML containing all messages for all levels, or NULL if
     * no messages exist.
     */
    public static function get_messages()
    {
        $messages = http::session('quixotic_messages');
        if (empty($messages)) {
            return NULL;
        }
        
        ob_start();
        
        foreach ($messages as $level => $batch) {
            foreach ($batch as $message) {
                echo self::display_message($message, $level);
            }
        }
        // Erase the messages, so as not to display them later.
        http::session_unset('quixotic_messages');
        
        // Return the resulting string.
        return ob_get_clean();
    }
    
    public static function display_message($message, $level)
    {
        ob_start();
        switch ($level) {
            case self::MSG_SUCCESS:
                echo '<div class="alert alert-success">'.$message.'</div>';
                break;
            case self::MSG_NOTICE:
                echo '<div class="alert alert-info">'.$message.'</div>';
                break;
            case self::MSG_WARNING:
                echo '<div class="alert alert-warning">'.$message.'</div>';
                break;
            case self::MSG_ERROR:
                echo '<div class="alert alert-danger">'.$message.'</div>';
                break;
        }
        return ob_get_clean();
    }
    
    /**
     * Returns the string containing the pagination bar.
     * 
     * If the number of the pages is too high (more than 15), shows only
     * the first 5 and the last 5 pages.
     * 
     * @param int $count Total count of all results.
     * @param int $limit Number of the results per page.
     * @param int $page Current page (starting with zero).
     * @return string Pagination HTML
     */
    public static function pagination($count, $limit, $page)
    {
        // If total count is less or equal than the page size,
        // no pagination needed.
        if ($count <= $limit) {
            return '';
        }
        
        $size = ceil($count / $limit);
        
        ob_start();
        
        echo '<nav>';
        echo '<ul class="pagination pagination-sm">';
        for ($i = 0; $i < $size; $i++) 
        {
            // Check if there's too many pages. If so, add a placeholder.
            if ($size > 15 && $i > 4 && $i < $size - 5) {
                if ($i == 5) {
                    echo '<li class="disabled"><a>&hellip;</a></li>';
                }
                continue;
            }
            // Build a new page URL and show it.
            $url = self::url(
                app::$request->query, 
                app::$request->args,
                $i,
                app::$request->order_by,
                app::$request->order_direction
            );
            echo '<li'.($i == $page ? ' class="active"' : '').'>';
            echo '<a href="'. $url .'">'. ($i + 1) .'</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</nav>';
        
        return ob_get_clean();
    }
    
    /**
     * Returns the human-readable date/time.
     * 
     * @param string $datetime Date/time string accepted by PHP DateTime class.
     * @param string $format Date format accepted by PHP DateTime class.
     * @param string $from_format Date format in which the $datetime is passed (optional).
     * @return string Human-readable date/time.
     * @throws app_exception
     */
    public static function datetime($datetime, $format = self::DATE_LOG, $from_format = NULL)
    {
        if (empty($datetime) || $datetime == '0000-00-00') {
            return 'N/A';
        }
        if (!empty($datetime) && $from_format == 'd/m/Y') {
            // Convert to US date format.
            list ($d, $m, $y) = explode('/', $datetime);
            $datetime = $m.'/'.$d.'/'.$y;
        }
        try {
            $dt = new \DateTime($datetime);
        } catch (\Exception $ex) {
            throw new app_exception($ex->getMessage());
        }
        return $dt->format($format);
    }
    
    /**
     * Returns the human-readable date.
     * 
     * @param string $datetime Date/time string accepted by PHP DateTime class.
     * @param string $format Date format accepted by PHP DateTime class.
     * @param string $from_format Date format in which the $datetime is passed (optional).
     * @return string Human-readable date/time.
     * @throws app_exception
     */
    public static function date($datetime, $format = 'd/m/Y', $from_format = NULL)
    {
        return self::datetime($datetime, $format, $from_format);
    }
    
    public static function datetime_age($datetime)
    {
        if (!$datetime instanceof \DateTime) {
            try {
                $start = new \DateTime($datetime);
            } catch (\Exception $ex) {
                throw new app_exception($ex->getMessage());
            }
        } else {
            $start = $datetime;
        }
        $end = new \DateTime(); 

        $interval = $end->diff($start); 
        return self::dateinterval_parsed($interval);
    }

    /**
     * Tells how much time has passed between this moment and the given datetime.
     * 
     * @param string $datetime Date/time string accepted by PHP DateTime class.
     * @return string Human-readable period ("now", "2 hours ago", etc.)
     * @throws app_exception
     */
    public static function datetime_ago($datetime)
    {
        try {
            $start = new \DateTime($datetime);
        } catch (\Exception $ex) {
            throw new app_exception($ex->getMessage());
        }
        $end = new \DateTime(); 

        $interval = $end->diff($start); 
        $result = self::dateinterval_parsed($interval);
        
        return (empty($result)) ? 'now' : $result .' ago';
    }
    
    /**
     * Accepts the time duration in minutes and returns the amount
     * of either minutes or hours, days, etc. - whichever is bigger.
     * 
     * @param int $value Duration in minutes.
     * @return string Human-readable duration.
     */
    public static function time_duration($value)
    {
        $d1 = new \DateTime();
        $d2 = new \DateTime();
        $d2->add(new \DateInterval('PT'.$value.'M'));
        $interval = $d2->diff($d1);
        return self::dateinterval_parsed($interval);
    }
    
    public static function plural($n, $str, $single = '', $plural = 's')
    {
        if ($n % 10 == 1 && $n !== 11) {
            return $n.' '.$str.$single;
        } else {
            return $n.' '.$str.$plural;
        }
    }
    
    private static function dateinterval_parsed($interval)
    {
        if (!$interval instanceof \DateInterval) {
            return '';
        }
        
        if ($interval->y !== 0) { 
            return self::plural($interval->y, "year"); 
        } elseif ($interval->m !== 0) { 
            return self::plural($interval->m, "month"); 
        } elseif ($interval->d !== 0) { 
            return self::plural($interval->d, "day"); 
        } elseif ($interval->h !== 0) { 
            return self::plural($interval->h, "hour"); 
        } elseif ($interval->i !== 0) { 
            return self::plural($interval->i, "minute");
        } else { 
            return ''; 
        } 
    }
    
    /**
     * Outputs a JSON-encoded parameter and terminates the script.
     * Used mainly for the AJAX form validation.
     * 
     * @param mixed $data Output data to be sent (message, or array of messages).
     * @param bool $success TRUE or FALSE, depending on the state (default: FALSE).
     * URL will be used. Default: empty.
     */
    public static function send_ajax_response($data, $success = FALSE)
    {
        // If we have an array of errors here, we have to pre-process it,
        // because it may contain complex keys with commas in them
        // (related to several form attributes at once).
        if (!$success && is_array($data)) {
            $errors = array();
            foreach ($data as $key => $value) {
                if (strpos($key, ',') !== FALSE) {
                    $keys = explode(',', $key);
                    foreach ($keys as $key) {
                        $errors[$key] = self::encode($value);
                    }
                } else {
                    $errors[$key] = self::encode($value);
                }
            }
            $data = $errors;
        }
        
        // HTML-encode the response strings.
        if (is_array($data)) {
            array_walk($data, function(&$item, $key){
                $item = self::encode($item);
            });
        } else {
            $data = self::encode($data);
        }
        
        // Build the resulting object.
        $object = new \stdClass();
        $object->success = $success;
        $object->data = $data;
        
        // Output JSON-endoded object and terminate the program.
        http::send_ajax_response($object);
    }
    
    public static function encode($value) {
        return htmlspecialchars($value);
    }
    
    public static function dump($var) {
        ob_start();
        var_dump($var);
        return ob_get_clean();
    }
}