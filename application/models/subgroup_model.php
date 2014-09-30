<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Subgroup_model extends CI_Model {


	public $offset;
	public $limit;

	private $_tables;
	private $_defaults;
	private $_parent = false;

	public function __construct() 
	{
		parent::__construct();
		$this->load->database();
		$this->load->config('subgroup_tables', TRUE);
		$this->_tables = $this->config->item('subgroup_tables');
		$this->_defaults = $this->_tables['defaults'];
		unset($this->_tables['defaults']);
	}

	public function group_id($name) {
		$query = $this->db->get_where($this->_tables['groups'], array('name' => $name));
		if($query->num_rows() == 0) { return false; };
		if($query->num_rows() > 1) { trigger_error("Multiple groups for with name: " . htmlentities($name), E_USER_WARNING); return false; };
		return $query->row()->id;
	}

	public function supgroups_where_parent($parent) {
		$parent = $this->_group_id($parent);
		$query = $this->db->get_where($this->_tables['subgroups'], array('parent' => $parent), $this->limit, $this->offset);
		$this->_reset_to_defaults();
		return $query;
	}

	public function subgroup($subgroup)
	{
		$identifier = 'name';
		if($subgroup == (string) (int) $subgroup) $identifier = 'id';
		$query = $this->db->get_where($this->_tables['subgroups'], array($identifier => $subgroup, 'parent' => $this->_parent));
		$this->_reset_to_defaults();
		return $query;
	}

	public function subgroup_id($subgroup)
	{
		$subgroup = $this->subgroup($subgroup);
		if($subgroup->num_rows() != 1) return false;
		return $subgroup->row()->id;
	}

	public function set_parent($parent = NULL)
	{
		if(!$parent) return $this->reset_parent();
		$this->_parent = $this->_group_id($parent);
		return true;
	}

	public function reset_parent()
	{
		$this->_parent = FALSE;
		return true;
	}

	public function set_user($id)
	{
		if($id == (string) (int) $id)
		$this->_user = $id;
	}

	public function user_in_group($group = NULL)
	{
		if(!$group) $group = $this->_parent;
		$group = $this->_group_id($group);
		$query = $this->db->get_where($this->_tables['users_groups'], array('user_id' => $this->_user, 'group_id' => $group));
		if($query->num_rows() == 1) return true;
		return false;
	}

	public function user_in_subgroup($group)
	{
		$group = $this->subgroup($group)->row();
		$query = $this->db->get_where($this->_tables['users_subgroups'], array('user_id' => $this->_user, 'group_id' => $group->id));
		if($query->num_rows() != 1) return false;
		return $this->user_in_group($group->parent);
	}

	private function _group_id($string) {
		if($string != (string) (int) $string)
		{
			$string = $this->group_id($string);
		}
		return $string;
	}	

	private function _subgroup_id($string)
	{
		if($string != (string) (int) $string)
		{
			$string = $this->subgroup_id($string);
		}
		return $string;
	}

	private function _reset_to_defaults()
	{
		$this->limit = $this->_defaults['limit'];
		$this->offset = $this->_defaults['offset'];
	}
}