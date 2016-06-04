<?php

namespace modules\user\models;

use core\app;
use core\http;
use core\db_arg;

class user extends \core\base_model
{
    public $avatar;
    public $avatar_path;
    public $companies = [];
    public $company_id;
    public $company_name;
    public $company_unit_id;
    public $company_unit_name;
    public $description;
    public $disabled;
    public $email;
    public $emails;
    public $firstname;
    public $fullname;
    public $lastname;
    public $permissions;
    public $phone;
    public $registered;
    public $token;
    public $user_type;
    public $username;
    
    protected $map = [
        'company_id' => 'id_company',
        'description' => 'comentarios',
        'firstname' => 'nombre_primero',
        'lastname' => 'nombre_patronimico',
        'phone' => 'telefono',
        'registered' => 'fecha_registro',
        'token' => 'pwdhash',
        'username' => 'id_usuario',
    ];
    
    public static function get_table() 
    {
        return 'tusuario';
    }
    
    public static function all_by_name($name)
    {
        if (empty($name)) {
            return FALSE;
        }
        
        $sql = "SELECT u.*
            FROM ". self::get_table() ." AS u
            WHERE CONCAT(u.nombre_primero, ' ', u.nombre_patronimico) LIKE :search
            ORDER BY u.nombre_primero, u.nombre_patronimico";
        $params['search'] = '%'.$name.'%';
        
        return self::all_sql($sql, $params);
    }
    
    public static function all($a = NULL) {
        // Show only non-deleted users.
        if (empty($a)) {
            $a = ['disabled' => 0];
        } else {
            if (is_array($a)) {
                $a['disabled'] = 0;
            } elseif ($a instanceof db_arg) {
                $a->conditions['disabled'] = 0;
            }
        }
        return parent::all($a);
    }
    
    public function rules()
    {
        // We need different sets of rules here, depending on whether we're
        // editing an existing user or adding a new one.
        if ($this->validation_context == 'edit') {
            return [
                ['id', 'id'],
                ['emails', 'user/emails'],
                ['company_id', 'number_positive_as_string'],
            ];
        } elseif ($this->validation_context == 'profile') {
            return [
                ['emails', 'user/emails'],
                [['password', 'password_confirm'], 'user/password'],
            ];
        } else {
            return [
                ['emails', 'user/emails'],
                ['company_id', 'number_positive_as_string'],
            ];
        }
    }

    public function init($data)
    {
        parent::init($data);
        
        $this->fullname = $this->get_full_name();
        $this->avatar_path = self::get_avatar_path($this->avatar);
        $this->permissions = self::load_permissions($this->id);
        $this->email = $this->load_primary_email();
    }
    
    private function get_full_name()
    {
        $fullname = $this->firstname;
        if (!empty($this->lastname)) {
            $fullname .= ' '. $this->lastname;
        }
        return $fullname;
    }
    
    private static function get_avatar_path($filename) {
        if (empty($filename)) {
            return '';
        }
        return '/images/avatars/'.$filename;
    }
    
    public function is_logged_in()
    {
        return (bool) self::logged_in_id();
    }
    
    public static function logged_in_id()
    {
        $logged_in_as = http::session('userid_loginas');
        return (!empty($logged_in_as)) ? $logged_in_as : http::session('userid');
    }
    
    public function login($username, $password)
    {
        if (empty($username) || empty($password)) {
            return FALSE;
        }
        
        $sql = "SELECT u.id 
            FROM ". self::get_table() ." u
            LEFT JOIN ". user_email::get_table() ." ue ON ue.user_id = u.id
            WHERE u.password = :hash
                AND u.disabled = 0
                AND (u.id_usuario = :name
                    OR ue.email = :name)";
        $params = [
            'name' => $username,
            'hash' => self::hash_password($password),
        ];
        $user = self::one_sql($sql, $params);
        
        if (empty($user)) {
            return FALSE;
        }
        
        // User is authenticated successfully.
        http::session_set('userid', $user->id);
        
        return $user->id;
    }
    
    public static function hash_password($password)
    {
        return md5($password);
    }
    
    public static function validate_emails($emails)
    {
        if (empty($emails)) {
            return [
                FALSE, 
                _('Please add emails for this user.')
            ];
        }
        return TRUE;
    }
    
    public static function validate_email($email)
    {
        // Check syntax.
        $result = \core\validation::validate_email($email);
        if (!is_bool($result)) {
            return $result;
        }
        // If this is a new user, also check if this email is already taken. 
        if (self::email_is_busy($email)) {
            return [
                FALSE, 
                _('This email is already taken. Please choose another.')
            ];
        }
        return TRUE;
    }
    
    public static function validate_password($password, $confirmation)
    {
        // If this is a new user, also check if this email is already taken. 
        if (!empty($password) && $password != $confirmation) {
            return [
                FALSE, 
                _('The passwords you entered do not match.')
            ];
        }
        return TRUE;
    }
    
    private static function email_is_busy($email) {
        return user_email::exists([
            'email' => $email,
            'deleted' => 0
        ]);
    }
    
    public function load_company_name()
    {
        if (empty($this->company_id)) {
            return NULL;
        }
        
        $company = \models\company::one([
            'id' => $this->company_id
        ], ['name']);
        if (empty($company)) {
            return NULL;
        }
        
        return $company->name;
    }
    
    public function load_emails()
    {
        return user_email::all_for_user($this->id);
    }
    
    public function load_primary_email()
    {
        $result = user_email::first_for_user($this->id);
        if (empty($result)) {
            return NULL;
        }
        return $result->email;
    }
    
    private static function build_filters($args = [])
    {
        // Limit access
        $where = [];
        $params = [];
        if (app::get_user()->has_access_to('users/all', role::CONTEXT_COMPANY)) {
            $where[] = "u.id_company " . self::sql_in(app::get_user()->load_all_companies());
        }
        
        // Search
        if (!empty($args[0]['search'])) {
            $where[] = "(CONCAT(u.nombre_primero, ' ', u.nombre_patronimico) LIKE :search
                OR ue2.email LIKE :search)";
            $params['search'] = '%'.$args[0]['search'].'%';
        }
        
        // Only non-deleted users
        $where[] = 'u.disabled = 0';
                
        return [$where, $params];
    }
    
    public static function all_by_company($company_id)
    {
        list ($where, $params) = self::build_filters();
        
        $where[] = 'u.id_company = :companyid';
        $params['companyid'] = $company_id;
        
        try {
            $results = self::all_filtered(NULL, NULL, $where, $params);
        } catch (app_exception $ex) {
            throw $ex;
        }
        
        return $results;
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
                u.*,
                c.name AS company_name,
                ue.email AS email
            FROM ". self::get_table() ." AS u
            LEFT JOIN ". company::get_table() ." AS c ON c.id = u.id_company
            LEFT JOIN ". user_email::get_table() ." AS ue ON ue.user_id = u.id
                AND ue.priority = 0
            LEFT JOIN ". user_email::get_table() ." AS ue2 ON ue2.user_id = u.id
            ".(!empty($where) ? 'WHERE '.implode(' AND ', $where) : '')."
            GROUP BY u.id
            ORDER BY u.nombre_primero, u.nombre_patronimico";
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
            FROM ". self::get_table() ." AS u
            LEFT JOIN ". user_email::get_table() ." AS ue2 ON ue2.user_id = u.id
            ".(!empty($where) ? 'WHERE '.implode(' AND ', $where) : '');
        
        try {
            $count = self::count_sql($sql, $params);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $count;
    }
    
    /**
     * Finds a single user record for the given username or email.
     * 
     * @param string $username Username or email
     * @return mixed User object, or FALSE on failure.
     */
    public static function find_by_username($username)
    {
        $sql = "SELECT u.* 
            FROM ". self::get_table() ." u
            LEFT JOIN ". user_email::get_table() ." ue ON ue.user_id = u.id
            WHERE u.disabled = 0
                AND (u.id_usuario = :name
                    OR ue.email = :name)";
        $params = [
            'name' => $username,
        ];
        return self::one_sql($sql, $params);
    }
    
    /**
     * Finds a single user record for the given token (used by the password
     * recovery page).
     * 
     * @param string $token Token
     * @return mixed User object, or FALSE on failure.
     */
    public static function find_by_token($token)
    {
        return self::one([
            'pwdhash' => $token,
        ]);
    }
    
    public static function password_recovery_token()
    {
        return md5(mt_rand(0, 99999). microtime(true));
    }
    
    public function get_role_assignments()
    {
        if (empty($this->id)) {
            return [];
        }
        return role_assignment::all_for_user($this->id);
    }
    
    public static function load_permissions($user_id)
    {
        $sql = "SELECT 
                rd.controller,
                rd.action,
                ra.context_id
            FROM ". role_assignment::get_table() ." AS ra
            LEFT JOIN ". role_definition::get_table() ." AS rd ON rd.role_id = ra.role_id
            WHERE ra.user_id = :userid";
        $params = [
            'userid' => $user_id
        ];
        $results = role_assignment::all_sql($sql, $params);
        if (!$results) {
            return [];
        }
        
        $perms = [];
        foreach ($results as $result) {
            $perms[$result->controller.'/'.$result->action] = $result->context_id;
        }
        return $perms;
    }
    
    /**
     * Determines whether this user has a right to perform the requested
     * action in the given context for the given model instance.
     * 
     * @param string $action Controller/action pair.
     * @param int $context_id Context level. If not specified, then any level
     * @param int $instance_id Model instance ID (optional)
     * is enough for this action to be allowed (e.g., to display a menu item).
     * @return boolean TRUE or FALSE
     */
    public function has_access_to($action, $context_id = NULL, $instance_id = NULL)
    {
		return true;
        // No login, no access.
        if (!$this->is_logged_in()) {
            return FALSE;
        }
        
        // This permission isn't set on any level.
        if (!isset($this->permissions[$action])) {
            return FALSE;
        }
        
        if (empty($context_id)) {
            // No context level check is required.
            return TRUE;
        } else {
            if (empty($instance_id)) {
                return $this->permissions[$action] == $context_id;
            } else {
                // TODO check the parent company access
                return $this->permissions[$action] == $context_id;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Soft-deletes users matching the conditions.
     * 
     * @param array $conditions Array of conditions.
     * @return boolean TRUE on success, FALSE on failure.
     */
    public static function delete_soft($conditions)
    {
        $a = new \core\db_arg();
        $a->data = new \stdClass();
        $a->data->disabled = 1;
        $a->conditions = $conditions;
        $deleted = self::update($a);
        
        if ($deleted) {
            // Exclude this user from getting any ticket comms.
            ticket_user::delete(['user_id' => $conditions['id']]);
        }
        
        // Soft-delete his emails as well.
        user_email::delete_soft_for_user($conditions['id']);
        
        return $deleted;
    }
    
    public static function find_by_email($email) 
    {
        return self::one_by_email($email);
    }
    
    public static function one_by_email($email)
    {
        $sql = "SELECT u.*
            FROM ". user_email::get_table() ." ue
            LEFT JOIN ". self::get_table() ." u ON u.id = ue.user_id
            WHERE ue.email = ?
                AND u.disabled = 0";
        $params = [$email];
        try {
            $user = self::one_sql($sql, $params);
        } catch (\core\app_exception $ex) {
            return false;
        }
        return $user;
    }
    
    public function save($data)
    {
        if (empty($data->password)) {
            unset($data->password);
        }
        unset($data->password_confirm);
        
        if (!empty($data->emails)) {
            $emails = $data->emails;
            unset($data->emails);
        }
            
        if (empty($data->username) && !empty($emails) && $this->validation_context !== 'profile') {
            $data->username = $emails[0];
        }
        
        $id = parent::save($data);
        if (empty($data->id)) {
            $data->id = $id;
        }
        
        // Save user's emails.
        if (!empty($emails)) {
            user_email::delete(['user_id' => $data->id]);
            foreach ($emails as $i => $email) {
                user_email::add($data->id, $email, $i);
            }
        }
        
        return $id;
    }
    
    public static function logs($id, $controllers = [], $offset = 0, $limit = 20)
    {
        $sql = "SELECT 
                l.*,
                CONCAT(u.nombre_primero, ' ', u.nombre_patronimico) AS user_name
            FROM ". \models\log::get_table() ." AS l
            LEFT JOIN ". \models\user::get_table() ." AS u ON u.id = l.user_id
            WHERE 
                (l.instance_id = ? AND l.controller = ?)
                OR
                (l.user_id = ?)
            ORDER BY l.entrytime DESC
            LIMIT ".$offset. ",".$limit;
        $params = [
            $id, 
            'users',
            $id, 
        ];
        return \models\log::all_sql($sql, $params);        
    }
    
    public static function logs_count($id, $controllers = [])
    {
        $sql = "SELECT COUNT(*) AS c
            FROM ". \models\log::get_table() ." AS l
            WHERE 
                (l.instance_id = ? AND l.controller = ?)
                OR
                (l.user_id = ?)";
        $params = [
            $id, 
            'users',
            $id, 
        ];
        return \models\log::count_sql($sql, $params);        
    }
    
    public static function ticket_owners()
    {
        $sql = "SELECT u.*
            FROM ". ticket_user::get_table() ." AS tu
            LEFT JOIN ". self::get_table() . " AS u ON u.id = tu.user_id
            LEFT JOIN ". ticket::get_table() . " AS t ON t.id_incidencia = tu.ticket_id
            WHERE t.deleted = 0
                AND tu.user_type = ?
                AND u.disabled = 0
            GROUP BY tu.user_id
            ORDER BY u.nombre_primero, u.nombre_patronimico";
        $params = [ticket_user::OWNER];
        return self::all_sql($sql, $params);
    }
    
    public static function ticket_possible_owners()
    {
        $sql = "SELECT u.*
            FROM ". self::get_table() ." AS u
            WHERE u.id_company = ?
                AND u.disabled = 0
            ORDER BY u.nombre_primero, u.nombre_patronimico";
        $params = [config::get('system_company_id')];
        return self::all_sql($sql, $params);
    }
    
    public static function all_for_team($id)
    {
        $sql = "SELECT 
                tu.*,
                CONCAT(u.nombre_primero, ' ', u.nombre_patronimico) AS user_fullname
            FROM ". team_user::get_table() ." AS tu
            LEFT JOIN ". self::get_table() ." AS u ON tu.user_id = u.id
            WHERE tu.team_id = ?
                AND u.disabled = 0";
        $params = [$id];
        return team_user::all_sql($sql, $params);
    }
    
    public static function all_for_team_possible($id)
    {
        $sql = "SELECT 
                u.*
            FROM ". team_user::get_table() ." AS tu
            LEFT JOIN ". self::get_table() ." AS u  ON tu.user_id = u.id
            WHERE u.disabled = 0
                AND tu.team_id <> ?
            GROUP BY u.id";
        $params = [$id];
        return self::all_sql($sql, $params);
    }

    public function load_config()
    {
        return user_config_entry::all_for_user($this->id);
    }
    
    /**
     * Recursively builds the array of company IDs available to the user.
     * 
     * @param int $company_id Company ID (user's company by default)
     * @return void
     */
    public function load_all_companies()
    {
        $results = [$this->company_id];
        
        $companies = company::all_children_for($this->company_id);
        if (empty($companies)) {
            return $results;
        }
        
        foreach ($companies as $company_id) {
            $results[] = $company_id;
        }
        
        return $results;
    }
    
    public function company_is_available($company_id)
    {
        return in_array($company_id, $this->load_all_companies());
    }
    
    public static function merge($source_id, $dest_id)
    {
        // Merge emails.
        $sql = "UPDATE ". user_email::get_table(). " SET 
                user_id = ?,
                priority = priority + 1
            WHERE user_id = ?";
        $params = [$dest_id, $source_id];
        self::run($sql, $params);
        
        // Merge ticket notes
        $sql = "UPDATE ". ticket_note::get_table(). " SET 
                author_id = ?
            WHERE author_id = ?";
        $params = [$dest_id, $source_id];
        self::run($sql, $params);
        
        // Adjust the ticket associations.
        $sql = "UPDATE ". ticket_user::get_table(). " SET 
                user_id = ?
            WHERE user_id = ?";
        $params = [$dest_id, $source_id];
        self::run($sql, $params);
        
        // Soft-delete the source user.
        $sql = "UPDATE ". self::get_table() ." SET
                disabled = 1
            WHERE id = ?";
        $params = [$source_id];
        self::run($sql, $params);
        
        return TRUE;
    }
    
    public static function all_for_company($id)
    {
        if (empty($id)) {
            return FALSE;
        }
        
        $sql = "SELECT u.*
            FROM ". self::get_table() . " AS u
            WHERE u.id_company = ?
            ORDER BY u.nombre_primero, u.nombre_patronimico";
        
        return self::all_sql($sql, [$id]);
    }
}
