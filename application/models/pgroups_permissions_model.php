<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pgroups_permissions_model extends CI_Model {

	private $_table = 'pgroups_permissions';
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function get_permissions_by_group($id) {
		$query = $this->db->get_where($this->_table, array('pgroup_id' => $id));
		return $query->result_array();
	}

	public function get_permission($id) {
		$query = $this->db->get_where($this->_table, array('permissions_id' => $id));
		return $query->result_array();
	}

	public function has_group_permission($groupid, $permissionid) {
		$query = $this->db->get_where($this->_table, array('pgroup_id' => $groupid, 'permissions_id' => $permissionid));
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

	public function update($gid, $pid, $value) {
		$this->db->where('pgroup_id', $gid);
		$this->db->where('permissions_id', $pid);
		return $this->db->update($this->_table, array('value' => $value));
	}

	public function insert($gid, $pid, $value) {
		return $this->db->insert($this->_table, array(
			'pgroup_id' => $gid,
			'permissions_id' => $pid,
			'value' => $value
		));
	}

	public function exists($gid, $pid) {
		$this->db->where('pgroup_id', $gid);
		$this->db->where('permissions_id', $pid);
		$this->db->limit(1, 0);
		return $this->db->get($this->_table)->row();
	}

	public function delete($gid, $pid) {
		$this->db->where('pgroup_id', $gid);
		$this->db->where('permissions_id', $pid);
		$this->db->delete($this->_table);
		return true;
	}

	public function group_has_permissions($group, $permissions, &$permissions_array = array()) {
		return $this->groupset_have_permissions(array($group), $permissions, $permissions_array);
	}

	public function groupset_have_permissions($groupset, $permissions, &$permissions_array = array()) {
		if(!is_array($permissions_array)) $permissions_array = array();
		foreach($groupset as $g) {
			$needed = array();
			// Check if permission was already read from db
			foreach($permissions as $p) {
				if(isset($permissions_array[$g][$p]) && $permissions_array[$g][$p] == false) return false;
				if(!isset($permissions_array[$g][$p])) $needed[] = $p;
				elseif($permissions_array[$g][$p] == true) unset($permissions[$p]);
			}

			if(count($needed) !== 0) {
				$this->db->where('pgroup_id', $g);
				$this->db->where_in('permissions_id', $needed);
				$result = $this->db->get($this->_table)->result_array();
				$res = true;
				foreach($result as $r) {
					if($r['value'] == false) $res = false;
					unset($permissions[array_search($r['permissions_id'], $permissions)]);
					$permissions_array[$g][$r['permissions_id']] = (int) $r['value'];
				}
			}
			foreach($permissions as $p) {
				$permissions_array[$g][$p] = NULL;
			}
			if(isset($res) && $res == false) return false;
			elseif(count($permissions) == 0) return true;
		}
		return $permissions;
	}


	public function set_group_permission($groupid, $permissionid, $value) {
		if($value == true) $value = true;
		else $value = false;
		$current_permission = $this->has_group_permission($groupid, $permissionid);
		if($current_permission===0)
		{
			$this->db->insert($this->_table, array(
				'group_id' => $groupid,
				'permission_id' => $permissionid,
				'value' => $value
			));
		} elseif($current_permission != $value) {
			$this->db->where('group_id', $groupid);
			$this->db->where('permission_id', $permissionid);
			$this->db->update($this->_table, array('value' => $value));
		}
	}

}