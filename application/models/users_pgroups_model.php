<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Users_pgroups_model extends CI_Model {
	private $_table = 'users_pgroups';

	public function __construct() {
		parent::__construct();
		$this->load->database();
	} 

	public function user_groups($userid, $limit = NULL, $start = NULL, $orderby = 'significance', $order = 'DESC') {
		$orderby = strtolower($orderby);
		$order = strtoupper($order);
		if($orderby != 'significance' && $orderby != 'group_id' && $orderby != 'user_id' && $orderby != 'id') $orderby = 'significance';
		if($order != 'ASC') $order = 'DESC';
		$this->db->order_by($orderby, $order);
		$this->db->where('user_id', $userid);
		$query = $this->db->get($this->_table, $limit, $start);
		return $query->result();
	}

	public function user_group_ids($userid, $orderby = 'significance', $order = 'DESC')
	{
		$return = array();
		$result = $this->user_groups($userid);
		foreach($result as $r) {
			$return[] = $r->group_id;
		}
		return $return;
	}

	public function group_users($groupid, $limit = NULL, $start = NULL) {
		$this->db->where('group_id', $groupid);
		$query = $this->db->get($this->_table, $limit, $start);
		return $query->result_array();
	}

	public function is_user_in_group($userid, $groupid) {
		$this->db->where('user_id', $userid);
		$this->db->where('group_id', $groupid);
		$query = $this->db->get($this->_table);
		if($query->num_rows() == 1) {
			return array($query->row()->significance);
		}
		return false;
	}

	public function users_highest_significance($userid) {
		$this->db->where('user_id', $userid);
		$this->db->order_by('significance', 'DESC');
		$query = $this->db->get($this->_table, 1);
		return $query->row()->significance;
	}

	public function users_lowest_significance($userid) {
		$this->db->where('user_id', $userid);
		$this->db->order_by('significance', 'ASC');
		$query = $this->db->get($this->_table, 1);
		return $query->row()->significance;
	}

	public function add_user_to_group($userid, $groupid, $significance = NULL) {
		if(!isint($significance)) $significance = $this->users_lowest_significance($userid) - 1;
		$data = array(
			'user_id'  		=> $userid,
			'group_id' 		=> $groupid,
			'significance' 	=> $significance,
		);

		$this->db->insert($this->_table, $data);
	}

	public function remove_user_from_group($userid, $groupid) {
		$this->db->where('user_id', $userid);
		$this->db->where('group_id', $groupid);
		$this->db->delete($this->_table);
	}

	public function remove($id) {
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

	public function change_significance($userid, $groupid, $significance) {
		$this->db->where('user_id', $userid);
		$this->db->where('group_id', $groupid);
		$this->db->update($this->_table, array('significance' => $significance));
	}
}