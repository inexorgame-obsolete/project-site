<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Permissions_model extends CI_Model {

	// The table in the database
	private $_table = 'permissions';

	/**
	 * Magic Method __construct();
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Gets a permission
	 * @param int $id permission-id
	 * @param bool $array Whether return as array (true) or object (false)
	 * @return mixed Permission: Based on $array as ARRAY or OBJECT
	 */
	public function get_permission($id, $array = false) {
		 $query = $this->db->get_where($this->_table, array('id' => $id));
		 if($array) return $query->row_array();
		 return $query->row();
	}

	/**
	 * Gets a permission by its name
	 * @param string $name The permission-name
	 * @return object The permission object
	 */
	public function get_permission_by_name($name) {
		$query = $this->db->get_where($this->_table, array('name' => $name));
		return $query->row();
	}

	/**
	 * Gets all child-permissions of a parent
	 * @param int $id The parent-permission
	 * @param string $order_by The column on which the order is based
	 * @param string $order 'DESC' for descending order, 'ASC' for ascending order
	 * @return array An array containing the permission-objects
	 */
	public function get_permissions_by_parent($id, $order_by = 'name', $order = 'ASC') {
		$this->db->order_by($order_by, $order);
		$query = $this->db->get_where($this->_table, array('parent' => $id));
		return $query->result_array();
	}

	/**
	 * Checks if a permission has childrens
	 * @param int $id The permission-id
	 * @return bool
	 */
	public function has_childrens($id) {
		$this->db->where('parent', $id);
		$this->db->limit(1, 0);
		if($this->db->get($this->_table)->num_rows() > 0) return true;
		return false;
	}

	/**
	 * Gets permissions based on the order
	 * @param bool $parents TRUE: only permissions which do not have parents
	 * @param int $start offset
	 * @param int $limit results-limit
	 * @param string $order_by order-column
	 * @param string $order order-type (ASC and DESC)
	 * @return object Object containing all permission-objects
	 */
	public function get_permissions($parents = false, $start = 0, $limit = 30, $order_by = 'name', $order = 'ASC') {
		if($parents == false) $this->db->where('parent', NULL);
		$this->db->order_by($order_by, $order);
		return $this->db->get($this->_table, $limit, $start)->result();
	}

	/**
	 * Get all permissions including childs of a parent
	 * @param int $id parent-permission-id
	 * @return object The parent-object containing the childs (accessible like $parent->childs[0]->name)
	 */
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

	/**
	 * Creates a new permission
	 * @param string $name permission-name
	 * @param string $description description-name
	 * @param bool $default default-value
	 * @param mixed $parent INT(parent-id) or NULL if no parent
	 */
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

	/**
	 * Maximum pagination based on results per page and if parents should be displayed
	 * @param int $limit results per page
	 * @param bool $parents Whether parents should be displayed
	 * @return int maximum pagination
	 */
	public function max_pagination($limit = 30, $parents = false) {
		if(!$parents) $this->db->where('parent', null);
		if(!isint($limit)) $limit = 30;
		return ceil($this->db->get($this->_table)->num_rows() / $limit);
	}
}