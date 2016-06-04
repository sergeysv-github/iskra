<?php

namespace core;

class app_exception extends \Exception
{
    public function __construct($message = "", $code = 0, $previous = NULL) 
    {
        parent::__construct($message, $code, $previous);
    }
    
    public function getBacktrace()
    {
        $trace = $this->getTrace();
        
        $length = count($trace);
        $result = array();
        for ($i = 0; $i < $length; $i++) 
        {
            ob_start();
            var_dump($trace[$i]['args']);
            $args = ob_get_clean();
            
            $line  = (!empty($trace[$i]['file'])) ? $trace[$i]['file'] : 'Unknown file';
            $line .= '('.(!empty($trace[$i]['line']) ? $trace[$i]['line'] : 0).'): ';
            $line .= (!empty($trace[$i]['class'])) ? $trace[$i]['class'].'->' : '';
            $line .= $trace[$i]['function'];
            $line .= '('.$args.')';
            $result[] = $line;
        }

        return $result;
    }
}
