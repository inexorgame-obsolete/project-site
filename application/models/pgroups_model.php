<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pgroups_model extends CI_Model {

	// The table in the database
	private $_table = 'pgroups';

	/**
	 * Magic Method __construct();
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Gets a group
	 * @param int $id group-id
	 * @return object group-object
	 */
	public function get_group($id) {
		$query = $this->db->get_where($this->_table, array('id' => $id));
		return $query->row();
	}

	/**
	 * Gets a group by its name
	 * @param string $name group-name
	 * @return mixed BOOL(FALSE) if group does not exist else OBJECT(group)
	 */
	public function get_group_by_name($name) {
		$query = $this->db->get_where($this->_table, array('LOWER(name)' => strtolower($name)));
		$r = $query->row();
		if(isset($r->name)) return $r;
		return false;
	}

	/**
	 * Inserts a permission group to the table
	 * @param $name The pgroup-name
	 * @param $description The pgroup-description
	 */
	public function add_group($name, $description) {
		$data = array(
			'name' => $name,
			'description' => $description
		);
		$this->db->insert($this->_table, $data);
	}

	/**
	 * Gets multiple groups based on order
	 * @param int $offset entry-offset
	 * @param int $limit entry-limit
	 * @param string $order_by Column on which the order is based
	 * @param string $order 'ASC' or 'DESC'
	 * @return object containing multiple group-objects
	 */
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

	/**
	 * Searches for groups
	 * @param string $search search-string
	 * @param int $offset entry-offset
	 * @param int $limit entry-limit
	 * @param string $order_by Column on which the order is based
	 * @param string $order 'ASC' or 'DESC'
	 * @return object containing multiple group-objects
	 */
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

	/**
	 * Maximum pagination based on results per page and the search string
	 * @param string $parents The search string
	 * @param int $limit results per page
	 * @return int maximum pagination
	 */
	public function search_max_pagination($search, $limit = 30) {
		if(!isint($limit)) $limit = 30;
		$this->db->like('name', $search);
		return ceil($this->db->get($this->_table)->num_rows() / $limit);
	}

	/**
	 * Maximum pagination based on results per page
	 * @param int $limit results per page
	 * @return int maximum pagination
	 */
	public function max_pagination($limit = 30) {
		if(!isint($limit)) $limit = 30;
		return ceil($this->db->get($this->_table)->num_rows() / $limit);
	}
}