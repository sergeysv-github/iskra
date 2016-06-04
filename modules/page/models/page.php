<?php

namespace modules\page\models;

use core\db_arg;

class page extends \core\base_model
{
    public $description;
    public $name;
    
    public static function get_table() {
        return 'pages';
    }
    
    public function rules()
    {
        return [
            ['name', 'string_not_empty'],
        ];
    }
        
    public static function one_for_view($conditions)
    {
        $sql = "SELECT 
                r.*
            FROM ". self::get_table() ." AS r
            WHERE r.id = :id";
        
        $data = null;
        try {
            $data = parent::one_sql($sql, $conditions);
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        return $data;
    }
        
    private static function build_filters($args = [])
    {
        // Limit access
        $where = [];
        $params = [];
        
        // Search
        if (!empty($args[0]['search'])) {
            $where[] = "o.name LIKE :search";
            $params['search'] = '%'.$args[0]['search'].'%';
        }
                        
        return [$where, $params];
    }

    public static function all_filtered(db_arg $a = NULL, $args = [], $where = [], $params = [])
    {
        if (empty($a)) {
            $a = new db_arg();
        }
        
        if (empty($where) && empty($params)) {
            list ($where, $params) = self::build_filters($args);
        }
        
        $a->sql = "SELECT 
                o.*
            FROM ". self::get_table() ." AS o
            ".(!empty($where) ? 'WHERE '.implode(' AND ', $where) : '')."
            ORDER BY o.name";
        $a->params = $params;
        
        try {
            $results = self::page_sql($a);
        } catch (core\app_exception $ex) {
            throw $ex;
        }
        
        return $results;
    }
    
    public static function all_filtered_count($args = [])
    {
        list ($where, $params) = self::build_filters($args);
        
        $sql = "SELECT COUNT(*) AS c
            FROM ". self::get_table() ." AS o
            ".(!empty($where) ? 'WHERE '.implode(' AND ', $where) : '');
        
        try {
            $count = self::count_sql($sql, $params);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $count;
    }

}
