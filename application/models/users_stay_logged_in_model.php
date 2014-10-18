<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Users_stay_logged_in_model extends CI_Model
{
	// The expiration-date of the cookie and database-entry
	private $_stay_logged_in;

	// The table in the database
	private $_table = 'users_stay_logged_in';

	/**
	 * Magic Method __contruct();
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->config('auth');
		$this->load->library('user_agent');
		$this->_stay_logged_in = time() + $this->config->item('stay_logged_in_time');
	}

	/**
	 * Inserts an entry in the database
	 * @param int $uid user-id
	 * @param mixed $expiration FALSE: use default else INT(seconds) from now on
	 * @return string Logged-in-code
	 */
	public function insert($uid, $expiration = false) {
		$this->clean();
		if(!isint($expiration)) $expiration = $this->_stay_logged_in;
		else $expiration += time();
		$charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz012345678901234567890123456789";
		$string = '';
		while(strlen($string) < 32) {
			$string .= $charset[rand(0, 81)];
		}
		$code = uniqid($string, true);
		if(hash_available('sha512')) {
			$code = hash('sha512', $code);
		} else {
			$code = str_shuffle($code);
		}
		$this->db->insert($this->_table, array(
			'user_id' 		=> $uid, 
			'code' 			=> $code, 
			'expiration' 	=> $expiration,
			'ip' 			=> ip(), 
			'browser' 		=> $this->agent->browser() . ' ' . $this->agent->version(),
			'system'  		=> $this->agent->platform()
		));
		$this->clean();
		return $code;
	}

	/**
	 * Get logged-in-codes for a user
	 * @param int $uid user-id
	 * @return object The active-record query-object
	 */
	public function get_for_user($uid) {
		$this->db->where('user_id', $uid);
		return $this->db->get($this->_table);
	}

	/**
	 * Checks if a logged-in code fits to a specified user
	 * @param int $uid user-id
	 * @param string $code the logged-in-code
	 * @param bool $extend Wheather the code should be extended
	 * @return bool TRUE if is correct
	 */
	public function check($uid, $code, $extend = false) {
		$this->clean();
		$results = $this->db->get_where($this->_table, array('user_id' => $uid, 'code' => $code))->num_rows();
		if($results == 1) { if($extend) $this->extend($uid, $code, $extend); return true; }
		if($results > 1) throw new Exception('Multiple users in database with same code and id.');
		if($results == 0) return false;

	}

	/**
	 * Extends a user-logged-in-code combination
	 * @param int $uid user-id
	 * @param string $code logged-in-code
	 * @param mixed $expiration FALSE: use default else INT(seconds) from now on
	 */
	public function extend($uid, $code, $expiration = false) {
		if(!isint($expiration)) $expiration = $this->_stay_logged_in;
		else $expiration += time();
		$this->clean();
		$this->db->where('user_id', $uid);
		$this->db->where('code', $code);
		$this->db->update($this->_table, array('expiration' => $expiration));
	}

	/**
	 * Removes a user-logged-in-code from the database
	 * @param int $uid user-id
	 * @param string $code the user-logged-in-code
	 * @return affected rows
	 */
	public function remove($uid, $code) {
		$this->db->where('user_id', $uid);
		$this->db->where('code', $code);
		$this->db->delete($this->_table);
		return $this->db->affected_rows();
	}

	/**
	 * Cleans table from all expired codes
	 * @return affected rows
	 */
	public function clean() {
		$this->db->where('expiration <', time());
		$this->db->delete($this->_table);
		return $this->db->affected_rows();
	}

}