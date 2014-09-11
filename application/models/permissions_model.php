<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Permission_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_permission($id) {
		 $query = $this->db->get_where('permissions', array('id' => $id));
		 return $query->row();
	}

	public function get_permission_by_name($name) {
		$query = $this->db->get_where('permissions', array('name' => $name));
		return $query->row();
	}

	public function get_permissions_by_parent($id) {
		$query = $this->db->get_where('permissions', array('parent' => $id));
		return $query->result_array();
	}


}