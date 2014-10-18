<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Users_permissions_model extends CI_Model {

	// The table in the database
	private $_table = 'users_permissions';

	/**
	 * Magic Method __construct();
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Get permissions for userid
	 * @param int $id user-id
	 * @return object containing permission-ids
	 */
	public function get_permissions_by_user($id) {
		$query = $this->db->get_where($this->_table, array('user_id' => $id));
		return $query->result_array();
	}

	/**
	 * Get permissions by id
	 * @param int $id permission-id
	 * @return object containing user-ids
	 */
	public function get_permission($id) {
		$query = $this->db->get_where($this->_table, array('permissions_id' => $id));
		return $query->result_array();
	}

	/**
	 * Checks if a user has a permission
	 * @param int $userid user-id
	 * @param int $permissionid permission-id
	 * @return mixed INT(0) if not set in table else BOOL, TRUE if has permission
	 */
	public function has_user_permission($userid, $permissionid) {
		$query = $this->db->get_where($this->_table, array('user_id' => $userid, 'permissions_id' => $permissionid));
		if($query->num_rows() === 1)
		{
			$data = $query->row();
			if($data->value == TRUE)
			{
				return true;
			} else {
				return false;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Sets the permission for a user
	 * @param int $userid user-id
	 * @param int $permissionid permission-id
	 * @param bool $value TRUE: user gets this permission; FALSE: permission denied
	 */
	public function set_user_permission($userid, $permissionid, $value) {
		if($value == true) $value = true;
		else $value = false;
		$current_permission = $this->has_user_permission($userid, $permissionid);
		if($current_permission===0)
		{
			$this->db->insert($this->_table, array(
				'user_id' => $userid,
				'permission_id' => $permissionid,
				'value' => $value
			));
		} elseif($current_permission != $value) {
			$this->db->where('user_id', $userid);
			$this->db->where('permission_id', $permissionid);
			$this->db->update($this->_table, array('value' => $value));
		}
	}

	/**
	 * Updates a permission-entry
	 * @param int $uid user-id
	 * @param int $pid permission-id
	 * @param bool $value the new value
	 */
	public function update($uid, $pid, $value) {
		$this->db->where('user_id', $uid);
		$this->db->where('permissions_id', $pid);
		$this->db->update($this->_table, array('value' => $value));
	}

	/**
	 * Inserts an entry in the table
	 * @param int $uid user-id 
	 * @param int $pid permission-id
	 * @param bool $value Wheter the user has the permission or not
	 */
	public function insert($uid, $pid, $value) {
		return $this->db->insert($this->_table, array(
			'user_id' => $uid,
			'permissions_id' => $pid,
			'value' => $value
		));
	}

	/**
	 * Checks if a permission is already set for a user
	 * @param int $uid user-id
	 * @param int $pid permission-id
	 * @return type
	 */
	public function exists($uid, $pid) {
		$this->db->where('user_id', $uid);
		$this->db->where('permissions_id', $pid);
		$this->db->limit(1, 0);
		if($this->db->get($this->_table)->num_rows() > 0) return true;
		return false;
	}

	/**
	 * Delets a permission-user
	 * @param int $uid user-id
	 * @param int $pid permission-id
	 */
	public function delete($uid, $pid) {
		$this->db->where('user_id', $uid);
		$this->db->where('permissions_id', $pid);
		$this->db->delete($this->_table);
	}

}