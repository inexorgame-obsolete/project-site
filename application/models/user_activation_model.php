<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_activation_model extends CI_Model {

	// The table in the database
	private $_table = 'user_activation';

	/**
	 * Magic Method __construct();
	 */
	public function __construct() {
		$this->load->database();
	}
	
	/**
	 * Removes expired entries from the database
	 */
	public function remove_expired()
	{
		$this->db->where('expiration <', date('Y-m-d H:i:s'));
		$this->db->delte($this->_table);
	}

	/**
	 * Inserts new entry in the database
	 * @param int $userid The user-id of the user which should be activated
	 * @param string $activationcode The activationcode which the user needs in order to activate his account
	 * @param string $date the expiration-date in "Y-m-d H:i:s" format; default: now + 2 days
	 */
	public function insert($userid, $activationcode, $date = false)
	{
		if(!is_string($date)) $date = date('Y-m-d H:i:s', time()+172800);
		$this->remove_users_codes($userid);
		$data = array(
			'user_id' => $userid,
			'code' => $activationcode,
			'expiration' => $date
		);
		$this->db->insert($this->_table, $data);
	}

	/**
	 * Removes all codes for a user
	 * @param int $userid user-id
	 */
	public function remove_users_codes($userid)
	{
		$this->db->where('user_id', $userid);
		$this->db->delete($this->_table);
	}

	/**
	 * Deletes an entry from the database
	 * @param int $id The activation-id
	 */
	public function delete($id) 
	{
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

	/**
	 * Checks if an entry is valid and not expired
	 * @param int $userid user-id
	 * @param int $activationcode activation-code
	 * @param bool $delete_on_valid delete if is valid
	 * @return bool TRUE if valid
	 */
	public function is_valid($userid, $activationcode, $delete_on_valid = false)
	{
		$this->db->where('user_id', $userid);
		$this->db->where('code', $activationcode);
		$query = $this->db->get($this->_table);
		$data = $query->row();
		if(isset($data->user_id))
		{
			if(strtotime($data->expiration) < time())
			{
				$this->delete($data->id);
				return false;
			}
			if($delete_on_valid) $this->delete($data->id);
			return true;
		}
		return false;
	}
}
