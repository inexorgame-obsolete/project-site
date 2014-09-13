<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Permissions_model extends CI_Model {

	private $_table = 'permissions';
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_permission($id) {
		 $query = $this->db->get_where($this->_table, array('id' => $id));
		 $result = $query->row();
		 return $query->row();
	}

	public function get_permission_by_name($name) {
		$query = $this->db->get_where($this->_table, array('name' => $name));
		return $query->row();
	}

	public function get_permissions($childs = false, $number = 30, $start = 0, $orderby = 'name', $order = 'ASC') {
		$orderby = strtolower($orderby);
		$order = strtoupper($order);
		if(isset($childs)) $childs = true;
		else $childs = false;
		if(!isint($number)) $number = 30;
		if(!isint($start)) $start = 0;
		if($orderby != 'id' && $orderby != 'name' && $orderby != 'description' && $orderby != 'parent' && $orderby != 'default') $orderby = 'name';
		if($order != 'DESC') $order = 'ASC';
		$this->db->order_by($orderby, $order);
		$this->db->limit($number, $start);
		$query = $this->db->get($this->_table);
		return $query->result();
	}

	public function get_max_pagination($posts = 30) {
		if(!isint($posts)) $posts = 30;
		$query = $this->db->get($this->_table);
		return ceil($query->num_rows() / $posts);
	}

	public function get_permissions_by_parent($id) {
		$query = $this->db->get_where($this->_table, array('parent' => $id));
		return $query->result();
	}

	public function new_permission($name, $description, $default, $parent = NULL) {
		if($default) $default = true;
		else $default = false;
		$data = array(
			'name' => $name,
			'description' => $description,
			'default' => $default
		);
		if(isint($parent)) {
			$data['parent'] = $parent;
		}
		$this->db->insert($this->_table, $data);
	}

}