<?php

namespace core;

abstract class base_model
{
    public $errors = [];
    public $id;
    public $validation_context;
    
    /**
     * If some of the model's properties are named differently in the database
     * (e.g., "id_of_the_type" rather than "type_id", they could be mapped to
     * their DB counterparts here. Example:
     * <pre>
     * $map = [
     *     'type_id' => 'id_of_the_type',
     *     ...
     * ];
     * </pre>
     * @var array 
     */
    protected $map = [];
    
    /**
     * Runs through all public properties of this model and assigns values to
     * them from the data object passed. If some fields remapping is needed
     * during that, $map property of the model will be used.
     * 
     * Model may override this method, if some of its properties should take
     * values from its existing properties (e.g., user's full name from his
     * first name and last name).
     * 
     * Try at all costs not to use any more DB queries here. If you're loading 
     * dozens of model entries using all(), they will all use this method
     * internally, and if you use additional DB queries here, this can
     * decrease the performance dramatically.
     * 
     * @param stdClass $data Object with data.
     */
    public function init($data) 
    {
        foreach ($this->map as $new_attr => $old_attr) {
            if (isset($data->$old_attr)) {
                $data->$new_attr = $data->$old_attr;
                unset($data->$old_attr);
            }
        }
        foreach ($this->attributes() as $name) {
            $this->$name = (!empty($data->$name)) ? $data->$name : NULL;
        }
    }
    
    /**
     * Returns the list of all public and non-static properties of this model.
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
     * Returns one entry of this model from the database. If no conditions
     * specified, the first table record will be returned.
     * 
     * @param array $conditions Conditions (default: none).
     * @param array $fields Fields to be returned (default: all).
     * @param string $table Table to select from (model's own table by default).
     * @return stdClass Object containing the entry data.
     */
    public static function one($conditions = [], $fields = [], $table = NULL)
    {
        $arg = new \core\db_arg();
        if (!empty($table)) {
            $arg->table = $table;
        } else {
            $arg->table = call_user_func(get_called_class().'::get_table');
        }
        $arg->conditions = $conditions;
        $arg->fields = $fields;
        $arg->limit = 1;
        
        try {
            $results = self::all($arg);
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        self::init_all($results);
        
        return (!$results) ? FALSE : reset($results);
    }

    /**
     * Runs through the raw database results and re-initializes them
     * according to the current model's rules using init() method.
     * 
     * @param array $results Array of DB entries.
     * @return type
     */
    public static function init_all(&$results)
    {
        if (empty($results)) {
            return;
        }
        $model_class = get_called_class();
        foreach ($results as &$result) {
            $model = new $model_class();
            $model->init($result);
            $result = $model;
        }
    }
    
    /**
     * Gets one record via custom SQL.
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return mixed Result object, or FALSE on failure.
     * @throws app_exception
     */
    public static function one_sql($sql, $params = [])
    {
        $arg = new db_arg();
        $arg->sql = $sql;
        $arg->params = $params;
        $arg->offset = 0;
        $arg->limit = 1;
        
        try {
            $results = self::page_sql($arg);
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        return (empty($results)) ? FALSE : reset($results);
    }
    
    /**
     * Gets all records via custom SQL.
     * 
     * @param string $sql
     * @param array $params
     * @throws app_exception
     */
    public static function all_sql($sql, $params = [])
    {
        try {
            $st = self::run($sql, $params);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        $results = array();
        while ($row = $st->fetch(\PDO::FETCH_OBJ)) {
            $results[] = $row;
        }
        
        self::init_all($results);
        
        return $results;
    }
    
    /**
     * If the argument passed into this function has "offset" and "limit"
     * properties specified, the resulting rows amount will be limited
     * according to them.
     * 
     * @param \core\db_arg $a DB argument.
     * @return mixed Result objects, or FALSE on failure.
     * @throws app_exception
     */
    public static function page_sql(db_arg $a)
    {
        $offset = (int) $a->offset;
        $limit = (int) $a->limit;
        if (!empty($a->limit)) {
            $a->sql .= ' LIMIT '.$offset.', '.$limit;
        }
        
        try {
            $results = self::all_sql($a->sql, $a->params);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Check if an entry exists with the given conditions.
     * 
     * @param array $conditions Conditions (default: none).
     * @return boolean TRUE if exists, or FALSE on failure.
     */
    public static function exists($conditions = array())
    {
        return (bool) self::one($conditions);
    }
    
    /**
     * Loads the database controller.
     * @return \core\database Database controller
     */
    public static function get_db()
    {
        return \core\app::get_db();
    }

    /**
     * Loads all entries of this model in the database, matching the
     * parameters set.
     * 
     * @param mixed $a DB argument, or an array of conditions.
     * @return mixed Array of records, or FALSE on failure.
     */
    public static function all($a = NULL)
    {
        if (empty($a)) {
            $a = new db_arg();
        } elseif (is_array($a)) {
            $conditions = $a;
            $a = new db_arg();
            $a->conditions = $conditions;
        }
        
        if (!empty($a->conditions)) {
            list($where, $params) = self::build_where($a->conditions);
        } else {
            $where = '';
            $params = NULL;
        }
        $offset = (int) $a->offset;
        $limit = (int) $a->limit;
        
        if (!empty($a->fields)) {
            $fields = implode(',', $a->fields);
        } else {
            $fields = '*';
        }
        
        if (empty($a->table)) {
            $a->table = call_user_func(get_called_class().'::get_table');
        }
        
        $sql = 'SELECT '. $fields .' FROM '. $a->table . $where;
        
        if (!empty($a->order)) {
            $sql .= ' ORDER BY ';
            $orderbys = [];
            foreach ($a->order as $order) {
                $orderbys[] = key($order).' '.reset($order);
            }
            $sql .= implode(', ', $orderbys);
        } elseif (!empty($a->order_by)) {
            $sql .= ' ORDER BY '.$a->order_by.' ';
            $sql .= (strtoupper($a->order_dir) == 'DESC') ? 'DESC' : 'ASC';
        }
        
        if (!empty($a->limit)) {
            $sql .= ' LIMIT '.$offset.', '.$limit;
        }
        
        try {
            $results = self::all_sql($sql, $params);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Loads the total count of all entries of this model in the database, 
     * matching the parameters set.
     * 
     * @param array $conditions Array of key-value parameters (default: empty).
     * @return mixed Number of records, or FALSE on failure.
     */
    public static function count($conditions = [])
    {
        $table = call_user_func(get_called_class().'::get_table');
        list ($where, $params) = self::build_where($conditions);
        
        $sql = 'SELECT COUNT(*) AS c FROM '. $table . $where;
        try {
            $count = self::count_sql($sql, $params);
        } catch (Exception $ex) {
            throw $ex;
        }
        
        return $count;
    }
    
    public static function count_sql($sql, $params)
    {
        try {
            $st = self::run($sql, $params);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        $result = $st->fetchObject();
        
        return (empty($result)) ? 0 : $result->c;
    }
    
    /**
     * Updates the existing model entry with the data provided. If entry 
     * doesn't exist (ID is empty), it will be created. All errors
     * occurred during this will be kept in $this->errors.
     * 
     * @param object $data_obj Data object.
     * @return mixed TRUE on success, FALSE on failure. If new entry is created,
     * its ID will be returned.
     */
    public function save($data_obj)
    {
        $data = clone $data_obj; // To keep the original properties intact.
        
        // Sanitize the data attributes, if needed.
        foreach ($this->map as $attr => $db_attr) {
            if (isset($data->$attr)) {
                $data->$db_attr = $data->$attr;
                unset($data->$attr);
            }
        }
        
        // Just in case we're saving random bits of data for an existing
        // model instance.
        if (empty($data->id) && !empty($this->id)) {
            $data->id = $this->id;
        }
    
        $arg = new db_arg();
        $arg->table = call_user_func(get_called_class().'::get_table');
        $arg->data = $data;
        
        $id_name = (!empty($this->map['id'])) ? $this->map['id'] : 'id';
        if (!empty($data->$id_name)) {
            $arg->conditions = [$id_name => $data->$id_name];
        }
        
        try {
            if (!empty($data->$id_name)) {
                $result = self::update($arg);
            } else {
                $result = self::insert($arg->data);
            }
        } catch (app_exception $ex) {
            $this->errors[] = $ex->getMessage();
            return FALSE;
        }
        
        if (!$result) {
            $this->errors[] = _('Unknown error while trying to update data.');
        }
        
        $this->id = $result;
        
        return $result;
    }
    
    /**
     * Validates the data object using the set of built-in or custom 
     * validators, depending on the particular model's set of rules.
     * 
     * @param object $data Data object.
     * @param string $context Validation context (default: empty)
     * @return boolean TRUE if the object is valid, FALSE if not.
     */
    public function validate($data, $context = NULL)
    {
        // Set the validation context, if needed. It will define the set of
        // validation rules.
        if (!empty($context)) {
            $this->validation_context = $context;
        }
        
        $rules = $this->rules();
        if (empty($rules)) {
            // No validation needed.
            return TRUE;
        }
        
        // Assume everything is valid by default. If only one value isn't,
        // then validation fails.
        $is_valid = TRUE;
        foreach ($rules as $rule_entry) 
        {
            // The first element of the entry is the model property/properties 
            // to be validated. The second is the validator rule.
            $attrs = $rule_entry[0];
            $rule = $rule_entry[1];
            
            // Get the validator function.
            $callback = validation::get_validator($rule);
            
            // Build the array of parameters for the validator.
            if (is_array($attrs)) {
                $params = [];
                foreach ($attrs as $attr) {
                    $params[] = (isset($data->$attr)) ? $data->$attr : NULL;
                }
                $attrs_string = implode(',',$attrs);
            } else {
                $params = (isset($data->$attrs)) ? [$data->$attrs] : [NULL];
                $attrs_string = $attrs;
            }
            
            // Add the object itself as the last argument.
            $params[] = $data;
            
            // Perform the actual validation. The callback will return TRUE
            // on success, or an array of [FALSE, <error message>].
            $result = call_user_func_array($callback, $params);
            
            // Was the validation successful?
            if (is_bool($result) && $result === TRUE) {
                $is_valid = $is_valid && TRUE;
                continue;
            }
            
            // If not, save the error message and continue the validation.
            if (is_array($result) && $result[0] === FALSE) {
                $this->errors[$attrs_string] = $result[1];
                $is_valid = $is_valid && FALSE;
            }
        }
        return $is_valid;
    }
    
    /**
     * Specifies validation rules for each particular model. Each rule is 
     * defined as a two-element array. If this array is empty, then no
     * validation is needed at all for this model.
     * 
     * The first value of the resulting array is either a string or an array of 
     * strings, and each of these strings contains a property name of a data
     * object to be validated. E.g.:
     * 
     *      return [
     *          ...
     *          ['email', 'email'],
     *          ...
     *      ];
     * 
     * will lead this model to assume that $data->email must be a valid email 
     * address.
     * 
     * The sedond value of the resulting array is a string containing a 
     * validator name. A built-in set of validator functions is specified in
     * \core\validation class. Custom validators can be passed as a 
     * classname/method value. E.g.
     * 
     *      return [
     *          ...
     *          [['id', 'username'], 'user/username'],
     *          ...
     *      ];
     * 
     * will trigger the \models\user::validate_username() method and pass
     * the $data->id and $data->username variables into it.
     * 
     * @return array Set of validation rules.
     */
    public function rules()
    {
        return [];
    }
    
    /**
     * Delete entry from the database.
     * 
     * @param array $conditions Conditions (default: none).
     * @param string $table Table to delete from (model's table by default).
     * @return boolean TRUE on success, or FALSE on failure.
     */
    public static function delete($conditions = [], $table = NULL)
    {
        if (empty($table)) {
            $table = call_user_func(get_called_class().'::get_table');
        }
        
        list ($where, $params) = self::build_where($conditions);
        
        $sql  = 'DELETE FROM '. $table . $where;
        
        try {
            $st = self::get_db()->prepare($sql);
        } catch (\PDOException $ex) {
            throw app_exception($ex->getMessage());
        }
        
        try {
            $result = $st->execute($params);
        } catch (\PDOException $ex) {
            throw app_exception('Unknown error while trying to delete data.');
        }
        
        return $result;
    }
    
    /**
     * Calculates the difference between the two data sets and outputs it
     * as a human-readable string.
     * 
     * Both object SHOULD BE of the same model class.
     * 
     * @param object $new_data New data set.
     * @param object $old_data Old data set.
     */
    public static function data_diff(&$new_data, &$old_data = NULL)
    {
        $info = [];
        
        if (!empty($old_data) && $new_data == $old_data) {
            return 'No changes were made.';
        }
        
        // Convert objects to array (otherwise it's not possible to iterate
        // via their properties if they are of stdClass type).
        $new_data_array = (array) $new_data;
        $old_data_array = (!empty($old_data)) ? (array) $old_data : NULL;
        
        foreach ($new_data_array as $attr => $value) 
        {
            // Properties which are objects are not processed here.
            if (is_object($new_data_array[$attr])) {
                continue;
            }
            
            if (isset($old_data_array[$attr])) {
                // Check if the attribute value has changed.
                if ($new_data_array[$attr] != $old_data_array[$attr]) {
                    $info[] = sprintf('%s: from "%s" to "%s"',
                        $attr, $old_data_array[$attr], $value);
                }
            } else {
                // The attribute was added.
                $info[] = sprintf('%s: set to "%s"',
                    $attr, $value);
            }
        }
        
        // Check if some attributes are gone.
        if (!empty($old_data_array)) {
            foreach ($old_data_array as $attr => $value) {
                if (!isset($new_data_array[$attr])) {
                    continue;
                }
                // Properties which are objects are not processed here.
                if (is_object($new_data_array[$attr])) {
                    continue;
                }
                if (!isset($new_data_array[$attr])) {
                    $info[] = sprintf('%s: removed, old value: "%s"',
                        $attr, $old_data_array[$attr]);
                }
            }
        }
        
        return implode("<br>\n", $info);
    }
    
    /**
     * Inserts a single record into the database.
     * 
     * @param mixed $data Object containing data, or array of such objects.
     * @param string $table Table name
     * @return mixed ID of a newly created record, or FALSE on failure.
     * @throws \core\app_exception
     */
    public static function insert(&$data, $table = NULL)
    {
        if (empty($data)) {
            return FALSE;
        } elseif (!is_array($data)) {
            // Convert the data parameter to array forcefully.
            $data = [$data];
        }
        
        if (empty($table)) {
            $table = call_user_func(get_called_class().'::get_table');
        }
        
        $fields = [];
        $placeholders = [];
        
        foreach (get_object_vars($data[0]) as $field => $value) {
            $fields[] = $field;
            $placeholders[] = '?';
        }
        
        $sql  = 'INSERT INTO '. $table .'(';
        $sql .= implode(',', $fields);
        $sql .= ') VALUES (';
        $sql .= implode(',', $placeholders);
        $sql .= ')';
        
        try {
            $st = self::get_db()->prepare($sql);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        foreach ($data as $row) {
            $params = [];
            foreach (get_object_vars($row) as $value) {
                $params[] = $value;
            }
            try {
                $result = $st->execute($params);
            } catch (\PDOException $ex) {
                throw new app_exception($ex->getMessage());
            }
        }
        
        return (!$result) ? FALSE : self::get_db()->lastInsertId();
    }
    
    /**
     * Update a single record into the database.
     * 
     * @param db_arg $a DB argument. Must have its "data" and "conditions" 
     * attributes set.
     * @return bool Returns TRUE on success, or FALSE on failure.
     * @throws \core\app_exception
     */
    public static function update(db_arg &$a)
    {
        if (empty($a->data)) {
            return FALSE;
        }
        
        if (empty($a->table)) {
            $a->table = call_user_func(get_called_class().'::get_table');
        }
        
        list($where, $where_params) = self::build_where($a->conditions);
        
        $fields = [];
        $params = [];
        foreach (get_object_vars($a->data) as $field => $value) {
            if ($field == 'id') {
                continue; // Don't update the ID.
            }
            $fields[] = $field .'=?';
            $params[] = $value;
        }
        $params = array_merge($params, $where_params);
        
        $sql  = 'UPDATE '. $a->table .' SET ';
        $sql .= implode(', ', $fields);
        $sql .= $where;
        
        try {
            $st = self::get_db()->prepare($sql);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        try {
            $result = $st->execute($params);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Builds an SQL "WHERE" statement and its parameters based on the
     * conditions passed.
     * 
     * @param array $conditions Conditions (field => value)
     * @return array Array of two elements:
     * <ul>
     * <li>"WHERE" SQL statement with placeholders</li>
     * <li>Parameters (actual values for the placeholders)</li>
     * </ul>
     */
    protected static function build_where($conditions = [])
    {
        if (empty($conditions)) {
            return [NULL, NULL];
        }
        
        $conds = [];
        foreach ($conditions as $field => $value) {
            $conds[] = $field .' = ?';
            $params[] = $value;
        }
        
        $sql = ' WHERE '. implode(' AND ', $conds);
        
        return [$sql, $params];
    }
    
    /**
     * Runs an arbitrary SQL query.
     * 
     * @param string $sql SQL statement
     * @param array $params Query parameters
     * @return PDOStatement PDO statement object.
     */
    public static function run($sql, $params = [])
    {
        try {
            $st = self::get_db()->prepare($sql);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        try {
            $st->execute($params);
        } catch (\PDOException $ex) {
            throw new app_exception($ex->getMessage());
        }
        
        return $st;
    }
    
    public static function value_now()
    {
        return \core\html::datetime('now', 'Y-m-d H:i:s');
    }
    
    public static function logs($id, $controllers = [], $offset = 0, $limit = 20)
    {
        if (empty($controllers)) {
            return FALSE;
        } elseif (!is_array($controllers)) {
            $controllers = [$controllers];
        }
        $sql = "SELECT 
                l.*,
                CONCAT(u.nombre_primero, ' ', u.nombre_patronimico) AS user_name
            FROM ". \models\log::get_table() ." AS l
            LEFT JOIN ". \models\user::get_table() ." AS u ON u.id = l.user_id
            WHERE l.instance_id = ? 
                AND l.controller IN (". implode(',', array_fill(0, count($controllers), '?')) .")
            ORDER BY l.entrytime DESC
            LIMIT ".$offset. ",".$limit;
        $params = array_merge([$id], $controllers);
        return \models\log::all_sql($sql, $params);        
    }
    
    public static function logs_count($id, $controllers = [])
    {
        if (empty($controllers)) {
            return FALSE;
        } elseif (!is_array($controllers)) {
            $controllers = [$controllers];
        }
        $sql = "SELECT COUNT(*) AS c
            FROM ". \models\log::get_table() ." AS l
            WHERE instance_id = ? 
                AND controller IN (". implode(',', array_fill(0, count($controllers), '?')) .")";
        $params = array_merge([$id], $controllers);
        return \models\log::count_sql($sql, $params);        
    }

    public static function sql_in($values)
    {
        if (!is_array($values)) {
            return ' = '.intval($values);
        }
        return 'IN ('. implode(',', $values) .')';
    }
}

class db_arg 
{
    // Contains SQL query with placeholders.
    public $conditions = [];
    public $data;
    public $fields = [];
    public $limit = 0;
    public $offset = 0;
    public $order = [];
    public $order_by = '';
    public $order_dir = 'ASC';
    public $params = [];
    public $sql = '';
    public $table = '';
    
    /**
     * Contains the following attributes:
     * <ul>
     * <li>string $sql - SQL query with placeholders.</li>
     * <li>array $conditions - Conditions with parameters.</li>
     * <li>string $table - Table name.</li>
     * <li>fields $fields - Fields to be returned.</li>
     * <li>object $data - Data (for INSERT or UPDATE).</li>
     * <li>int $limit - Number of records to be returned.</li>
     * <li>int $offset - From which record to start.</li>
     * <li>string $order_by - Field to order the results by.</li>
     * <li>string $order_dir - Order direction. Default: ASC.</li>
     * <li>array $order - Array of [order by => order dir].</li>
     * </ul>
     */
    public function __construct() {}
}
