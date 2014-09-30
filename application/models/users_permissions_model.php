<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Users_permissions_model extends CI_Model {

	private $_table = 'users_permissions';
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_permissions_by_user($id) {
		$query = $this->db->get_where($this->_table, array('user_id' => $id));
		return $query->result_array();
	}

	public function get_permission($id) {
		$query = $this->db->get_where($this->_table, array('permissions_id' => $id));
		return $query->result_array();
	}

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

}