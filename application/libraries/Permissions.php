<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Permissions {

	private $_CI;
	private $_userid;
	private $_groupid;
	private $_permissions = array();
	private $_permission_names = array();
	private $_user_permissions = array();
	private $_group_permissions = array();
	public function __construct() {
		$this->_CI =& get_instance();
		$this->_CI->load->model('permissions_model');
		$this->_CI->load->model('pgroups_model');
		$this->_CI->load->model('users_permissions_model');
		$this->_CI->load->model('users_pgroups_model');
		$this->_CI->load->model('pgroups_permissions_model');
	}

	public function set_user($userid) {
		if(isint($userid) == $userid) {
			$this->_userid = (int) $userid;
			return true;
		}
		return false;
	}

	public function set_group($groupid) {
		$this->_groupid = $groupid;
	}

	public function has_user_permission($permission, $id = false) {
		if(!isint($id)) $id = $this->_userid;
		if($id != false) {
			$permission = $this->_get_permission($permission);
			if(isset($this->_user_permissions[$id][$permission->id])) return $this->_user_permissions[$id][$permission->id];
			$required_permissions = $this->get_all_parents($permission->id);
			$required_permissions[] = (int) $permission->id;

			// Check if permissions are directly assigned to user
			foreach($required_permissions as $i => $p) {
				$has_user_permission = $this->_CI->users_permissions_model->has_user_permission($id, $p);
				if($has_user_permission === false) { 
					$this->_user_permissions[$id][$p] = false;
					$this->_user_permissions[$id][$$permission->id] = false;
					return false; 
				}
				elseif($has_user_permission === true) 
				{
					$this->_user_permissions[$id][$p] = true;
					unset($required_permissions[$i]);
				}
			}
			if(count($required_permissions) == 0) 
			{
				$this->_user_permissions[$id][$permission->id] = true;
				return true;
			}

			// Check if permissions are assigned to group and fit the rules
			$required_permissions = $this->_CI->pgroups_permissions_model->groupset_have_permissions(
				$this->_CI->users_pgroups_model->user_group_ids($id), 
				$required_permissions, 
				$this->_group_permissions
			);
			if(is_bool($required_permissions)) 
			{
				$this->_user_permissions[$id][$permission->id] = $required_permissions;
				return $required_permissions;
			}

			// Use default $permissions
			foreach($required_permissions as $p) {
				if($this->_get_permission($p)->default == false) 
				{
					$this->_user_permissions[$id][$permission->id] = false;
					return false;
				}
			}

			$this->_user_permissions[$id][$permission->id] = true;
			return true;
		}

		$this->_user_permissions[$id][$permission->id] = false;
		return false;
	}

	public function has_user_permissions($permissions, $id = false, $return_array = false, $have_only_one = false)
	{
		$return = array();
		foreach($permissions as $p) {
			$return[$p] = $this->has_user_permission($p, $id);
			if($return[$p] == false && $have_only_one == false && $return_array == false) return false;
			elseif($return[$p] == true && $have_only_one == true && $return_array == false) return true;
		}
		if($return_array == true) return $return;
		return true; 
	}

	public function get_all_parents($id, $parents = false) {
		$permission = $this->_get_permission($id);
		if(!is_array($parents)) $parents = array();
		if(isset($permission->parent) && $permission->parent != NULL) {
			isint($permission->parent);
			$parents   = call_user_func_array(array($this, __FUNCTION__), array($permission->parent, $parents));
			$parents[] = $permission->parent;
		}
		return $parents;
	}

	private function _get_permission($code) {
		if(isint($code)) $permission = $this->_CI->permissions_model->get_permission($code);
		else $permission = $this->_CI->permissions_model->get_permission_by_name($code); 
		if(count($permission) === 0) { return false; }
		return $permission;
	}
}