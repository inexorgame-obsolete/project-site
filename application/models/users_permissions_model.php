<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Users_permissions_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_permissions_by_user($id) {
		$query = $this->db->get_where('users_permissions', array('user_id' => $id));
		return $query->result_array();
	}

	public function get_permissions_by_permission($id) {
		$query = $this->db->get_where('users_permissions', array('permissions_id' => $id));
		return $query->result_array();
	}

	public function has_user_permission($userid, $permissionid) {
		$query = $this->db->get_where('users_permissions', array('user_id' => $userid, 'permissions_id' => $permissionid));
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

}