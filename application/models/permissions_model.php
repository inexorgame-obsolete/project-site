<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Permissions_model extends CI_Model {
	private $_table = 'permissions';
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_permission($id, $array = false) {
		 $query = $this->db->get_where($this->_table, array('id' => $id));
		 if($array) return $query->row_array();
		 return $query->row();
	}

	public function get_permission_by_name($name) {
		$query = $this->db->get_where($this->_table, array('name' => $name));
		return $query->row();
	}

	public function get_permissions_by_parent($id, $order_by = 'name', $order = 'ASC') {
		$this->db->order_by($order_by, $order);
		$query = $this->db->get_where($this->_table, array('parent' => $id));
		return $query->result_array();
	}
	public function has_childrens($id) {
		$this->db->where('parent', $id);
		$this->db->limit(1, 0);
		if($this->db->get($this->_table)->num_rows() > 0) return true;
		return false;
	}
	public function get_permissions($no_parent = true, $start = 0, $limit = 30, $order_by = 'name', $order = 'ASC') {
		if($no_parent == true) $this->db->where('parent', NULL);
		$this->db->order_by($order_by, $order);
		return $this->db->get($this->_table, $limit, $start)->result();
	}

	public function get_permissions_recursive($id) {
		if(is_object($id)) $id = (array) $id;
		if(!is_array($id)) $return = $this->get_permission($id, true);
		else $return = $id;
		$return['childs'] = $this->get_permissions_by_parent($return['id']);
		foreach($return['childs'] as $i => $p)
		{
			$return['childs'][$i] = $this->get_permissions_recursive($return['childs'][$i]);
			if(count($return['childs'][$i]['childs']) == 0) $return['childs'][$i]['childs'] = false;
		}
		return $return;
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

	public function max_pagination($limit = 30, $parents = false) {
		if(!$parents) $this->db->where('parent', null);
		if(!isint($limit)) $limit = 30;
		return ceil($this->db->get($this->_table)->num_rows() / $limit);
	}
}