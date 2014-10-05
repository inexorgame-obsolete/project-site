<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pgroups_model extends CI_Model {

	private $_table = 'pgroups';

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_group($id) {
		 $query = $this->db->get_where($this->_table, array('id' => $id));
		 return $query->row();
	}

	public function get_group_by_name($name) {
		$query = $this->db->get_where($this->_table, array('LOWER(name)' => strtolower($name)));
		$r = $query->row();
		if(isset($r->name)) return $r;
		return false;
	}

	public function get_permissions_by_parent($id) {
		$query = $this->db->get_where('permissions', array('parent' => $id));
		return $query->result_array();
	}

	public function add_group($name, $description) {
		$data = array(
			'name' => $name,
			'description' => $description
		);
		$this->db->insert($this->_table, $data);
	}

	public function change_significance($id, $significance) {
		$this->db->where('id', $id);
		$this->db->update($this->_table, array('significance' => $significance));
	}

	public function get_groups($offset = 0, $limit = 30, $order_by = 'name', $order = 'ASC') {
		if(strtolower($order) == 'desc') $order = 'DESC';
		else $order = 'ASC';
		if(strtolower($order_by) != ('name' || 'id' || 'description')) $order_by = 'name';
		if(!isint($offset)) $offset = 0;
		if(!isint($limit)) $limit = 30;
		$this->db->limit($limit, $offset);
		$this->db->order_by($order_by, $order);
		return $this->db->get($this->_table)->result();
	}

	public function search_groups($search, $offset = 0, $limit = 30, $order_by = 'name', $order = 'ASC') {
		if(strtolower($order) == 'desc') $order = 'DESC';
		else $order = 'ASC';
		if(strtolower($order_by) != ('name' || 'id' || 'description')) $order_by = 'name';
		if(!isint($offset)) $offset = 0;
		if(!isint($limit)) $limit = 30;
		$this->db->limit($limit, $offset);
		$this->db->order_by($order_by, $order);
		$this->db->like('name', $search);
		return $this->db->get($this->_table)->result();
	}

	public function search_max_pagination($search, $limit = 30) {
		if(!isint($limit)) $limit = 30;
		$this->db->like('name', $search);
		return ceil($this->db->get($this->_table)->num_rows() / $limit);
	}

	public function max_pagination($limit = 30) {
		if(!isint($limit)) $limit = 30;
		return ceil($this->db->get($this->_table)->num_rows() / $limit);
	}
}