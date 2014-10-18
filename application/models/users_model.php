<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Users_model extends CI_Model {

	// The table in the database
	private $_table = 'users';

	// Hash-algorithms used for the password-hashing (all available will be used)
	private $_hash_algos = array('whirlpool', 'sha512');

	// The dir of the data-folder of the user
	private $_data_dir = false;

	/**
	 * Magic Method __construct();
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
		if(file_exists(FCPATH . 'application/config/data.php')) {
			$this->config->load('data', true);
			$dirs = $this->config->item('data')['dir'];
			if(isset($dirs['user'])) $this->_data_dir = $dirs['user'];
			if(strlen($this->_data_dir) > 0 && $this->_data_dir[strlen($this->_data_dir)-1] != "/" && $this->_data_dir[strlen($this->_data_dir)-1] != "\\") {
				$this->_data_dir .= '/';
			}
		}
	}

	/**
	 * Gets a user from the database
	 * @param int $id user-id
	 * @return object user-object
	 */
	public function user($id) {
		$this->db->where('id', $id);
		$query = $this->db->get($this->_table);
		return $query->row();
	}

	/**
	 * Update a user
	 * @param int $id user-id
	 * @param array $data new, changed data
	 * @param bool $password_is_hashed if password is changed; whether it needs to be hashed
	 */
	public function update_user($id, $data, $password_is_hashed = FALSE) {
		if(!$password_is_hashed && isset($data['password'])) $data['password'] = $this->real_hash_password($id, $data['password']);
		$this->db->where('id', $id);
		$this->db->update($this->_table, $data);
	}

	/**
	 * Searches for users
	 * @param string $name search-string
	 * @param array $columns search-columns
	 * @param int $limit search-limit
	 * @param int $offset search-offset
	 * @param bool $start % before search-string
	 * @param bool $end % after search-string
	 * @param string $by the column on which the search is based
	 * @param string $order ASC or DESC
	 * @return object containing user-objects
	 */
	public function users_like($name, $columns = array('username', 'ingame_name'), $limit = NULL, $offset = NULL, $start = true, $end = true, $by = 'username', $order = 'ASC') {
		if(is_array($name)) {
			if(isset($name['columns']) && is_array($name['columns']))           $columns = $name['columns']; 
			if(isset($name['limit'])   && isint($name['limit']))                $limit   = $name['limit'];   
			if(isset($name['offset'])  && isint($name['offset']))               $offset  = $name['offset'];  
			if(isset($name['start'])   && $name['start'] == false)              $start   = false;             else $start = true;
			if(isset($name['end'])     && $name['end'] == false)                $end     = false;             else $end   = true;
			if(isset($name['order'])   && strtolower($name['order']) == 'desc') $order   = 'DESC';            else $order = 'ASC';
			if(isset($name['order_by']))                                        $by      = $name['order_by'];
		elseif(isset($name['by']))                                              $by      = $name['by'];
			if(isset($name['name']))                                            $name    = $name['name'];
			else { throw new Exception('No name set at array for users_like.'); return; }
		}
		$this->db->limit($limit, $offset);
		$this->db->order_by($by, $order);
		$this->db->like($columns[0], $name);
		unset($columns[0]);
		foreach($columns as $column) {
			$this->db->or_like($column, $name);
		}
		return $this->db->get($this->_table)->result();

	}

	/**
	 * Gets a user-object by the username
	 * @param string $username user-name
	 * @return object user-object
	 */
	public function user_by_username($username) {
		$this->db->where('username', $username);
		$query = $this->db->get($this->_table);
		return $query->row();
	}

	/**
	 * Get user by unique_id
	 * @param string $unique_id user-unique-id
	 * @return object user-object
	 */
	public function user_by_unique_id($unique_id) {
		$this->db->where('unique_id', $unique_id);
		$query = $this->db->get($this->_table);
		return $query->row();
	}

	/**
	 * Gets a user by e-mail
	 * @param string $email user-email
	 * @return object user-object
	 */
	public function user_by_email($email) {
		$this->db->where('email', $email);
		$query = $this->db->get($this->_table, $limit, $start);
		return $query->row();
	}

	/**
	 * Gets all users with a specific country-code
	 * @param string $code 3-ISO-Letter
	 * @param int $limit results-limit
	 * @param int $start results-offset
	 * @return object containing user-objects
	 */
	public function users_by_country_code($code, $limit = NULL, $start = NULL) {
		$this->db->where('country_code', $code);
		$query = $this->db->get($this->_table, $limit, $start);
		return $query->result();
	}

	/**
	 * Gets all users
	 * @param string $orderby order-column
	 * @param string $order order-type (ASC or DESC)
	 * @param int $limit result-limit
	 * @param int $start result-offset
	 * @return object containing user-objects
	 */
	public function users($orderby = 'username', $order = 'ASC', $limit = 50, $start = 0) {
		$orderby = strtolower($orderby);
		$order = strtoupper($order);
		if(!$this->db->field_exists($orderby)) $orderby = 'username';
		if($order != 'DESC') $order = 'ASC';
		$this->db->order_by($orderby, $order);
		$query = $this->db->get($this->_table, $limit, $start);
		return $query->result();
	}

	/**
	 * Checks if a username exists
	 * @param string $username user-name to check
	 * @return bool TRUE if exists
	 */
	public function user_exists($username) {
		$this->db->where('username', $username);
		if($this->db->get($this->_table)->num_rows() > 0) return true;
		return false;
	}

	/**
	 * Check if an ingame_name exists
	 * @param string $name ingame-name to check
	 * @return bool TRUE if exists
	 */
	public function ingame_name_exists($name) {
		$this->db->where('ingame_name', $name);
		if($this->db->get($this->_table)->num_rows() > 0) return true;
		return false;
	}

	/**
	 * Creates a user
	 * @param string $email user-email
	 * @param string $username username
	 * @param string $ingame_name ingame_name
	 * @param string $password not hashed password
	 * @param string $register_ip the registration ip
	 * @param string $country_code 3-letter ISO-code
	 * @param bool $active Active or not TRUE: Active
	 * @return bool false if username already exists
	 */
	public function create($email, $username, $ingame_name, $password, $register_ip, $country_code, $active) {
		// The username should be validated before.
		// Because of security-reasons it will be checked here as well.
		// Will trigger Error (Exception would show password)
		if($this->user_exists($username)) {
			trigger_error('User with username \'' . htmlentities($username) . '\' already exists.', E_USER_ERROR);
			return false;
		}
		$first_data = array(
			'email' 		=> $email,
			'username' 		=> $username,
			'ingame_name' 	=> $ingame_name,
			'register_ip' 	=> $register_ip,
			'latest_ip'		=> $register_ip,
			'country_code' 	=> $country_code,
			'active' 		=> 0,
			'timestamp'		=> date('Y-m-d H:i:s')
		);

		$this->db->insert($this->_table, $first_data);
		$u = $this->user_by_username($username);
		$fielddata = $this->db->field_data($this->_table);
		$id_maxlen = 11;
		foreach($fielddata as $f) { if($f->name == 'id') { $id_maxlen = $f->max_length; break; }}
		$unique_id = uniqid(str_pad($u->id, $fielddata[0]->max_length, '0'), true);
		$password = $this->hash_password($unique_id, $password);
		$second_data = array(
			'unique_id' => $unique_id,
			'password'	=> $password,
			'active'	=> $active
		);
		$this->db->where('id', $u->id);
		$this->db->update($this->_table, $second_data);
		if(is_string($this->_data_dir)) mkdir(FCPATH . $this->_data_dir . $unique_id . '/');
		return true;
	}

	/**
	 * Check if username and password matches
	 * @param string $username user-name
	 * @param string $password unhashed user-password
	 * @param bool $active Whether the user needs to be active 
	 * @param bool $email Whether $username is an e-mail
	 * @return mixed user-id if matches, else BOOL(FALSE)
	 */
	public function check_password($username, $password, $active = TRUE, $email = FALSE)
	{
		$this->db->where($email ? 'email' : 'username', $username);
		$u = $this->db->get($this->_table)->row();
		if(!$u) return false;
		if($u->active == false && $active == true) return 0;
		if($u->password == $this->hash_password($u->unique_id, $password))
		{
			return $u->id;
		}
		return false;
	}

	/**
	 * Checks if userid and password matches 
	 * @param int $id user-id
	 * @param string $password unhashed user-password
	 * @param bool $active Wheter the user needs to be active
	 * @return bool TRUE if matches
	 */
	public function check_password_id($id, $password, $active = TRUE)
	{
		$this->db->where('id', $id);
		$u = $this->db->get($this->_table)->row();
		if(!$u) return false;
		if($u->active == false && $active == true) return 0;
		if($u->password == $this->hash_password($u->unique_id, $password))
		{
			return true;
		}
		return false;
	}

	/**
	 * Hashes a password based on the unique-id
	 * @param int $id user-id
	 * @param string $password unhashed password
	 * @return mixed STRING(password) hashed; BOOL(FALSE) if user does not exist
	 */
	public function real_hash_password($id, $password) {
		$user = $this->user($id);
		if(!$user) return false;
		return $this->hash_password($user->unique_id, $password);
	}

	/**
	 * Hashes a password
	 * @param string $salt password-salt
	 * @param string $password unhashed password
	 * @return string hashed password
	 */
	public function hash_password($salt, $password) {
		$hashes = hash_algos();
		$alogs = array();
		$return = $password;
		foreach($this->_hash_algos as $h) {
			if(in_array(strtolower($h), $hashes)) {
				$return = hash($h, $salt . $return);
			}
		}
		return $return;
	}
	
	/**
	 * Returns the maximum username-length
	 * @return mixed BOOL(FALSE) if not set in the config; ELSE INT(maxlength)
	 */
	public function max_username_length() {
		$fielddata = $this->db->field_data($this->_table);
		foreach($fielddata as $f) { if($f->name == 'username') { return (int) $f->max_length; }}
		return false;
	}

	/**
	 * Returns the maximum ingame-name-length
	 * @return mixed BOOL(FALSE) if not set in the config; ELSE INT(maxlength)
	 */
	public function max_ingame_name_length() {
		$fielddata = $this->db->field_data($this->_table);
		foreach($fielddata as $f) { if($f->name == 'ingame_name') { return (int) $f->max_length; }}
		return false;
	}

}