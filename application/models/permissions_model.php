<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Permissions_model extends CI_Model {

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

	public function new_permission($name, $description, $default, $parent = NULL) {
		if($default) $default = true;
		else $default = false;
		$data = array(
			'name' => $name,
			'description' => $description,
			'default' => $default
		);
		if(is_int($parent)) {
			$data['parent'] = $parent;
		}
		$this->db->insert($this->_table, $data);
	}

}