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

	public function has_user_permission($permission) {
		$has_permission = true;
		if($this->_userid != false) {
			$permission = $this->_get_permission($permission);
			$required_permissions = $this->get_all_parents($permission->id);
			$required_permissions[] = (int) $permission->id;

			// Check if permissions are directly assigned to user
			foreach($required_permissions as $p) {
				$has_user_permission = $this->_CI->users_permissions_model->has_user_permission($this->_userid, $p);
				if($has_user_permission===false) return false;
				elseif($has_user_permission===true) unset($required_permissions[$p]);
			}

			// Check if permissions are assigned to group and fit the rules
			$required_permissions = $this->_CI->pgroups_permissions_model->groupset_have_permissions(
				$this->_CI->users_pgroups_model->user_group_ids($this->_userid), 
				$required_permissions, 
				$this->_group_permissions
			);
			if(is_bool($required_permissions)) return $required_permissions;

			// Use default $permissions
			foreach($required_permissions as $p) {
				if($this->_get_permission($p)->default == false) return false;
				else unset($required_permissions[$p]);
			}
			return true;
		}
		return false;
	}

	public function get_all_parents($id, $parents = false) {
		$permission = $this->_get_permission($id);
		if(!is_array($parents)) $parents = array();
		if(isset($permission->parent) && $permission->parent != NULL) {
			$permission->parent = (int) $permission->parent;
			$parents   = call_user_func_array(array($this, __FUNCTION__), array($permission->parent, $parents));
			$parents[] = $permission->parent;
		}
		return $parents;
	}

	private function _get_permission($code) {
		if($permission = $this->_get_permission_from_array($code)) {
			return $permission;
		}
		if(is_int($code)) $permission = $this->_CI->permissions_model->get_permission($code);
		else $permission = $this->_CI->permissions_model->get_permission_by_name($code); 
		if(count($permission) === 0) { return false; }
		$this->_permissions[$permission->id] = $permission;
		$this->_permission_names[strtolower($permission->name)] = $permission->id;
		return $permission;
	}

	private function _get_permission_from_array($code) {
		if(is_int($code) && isset($this->_permissions[$code])) {
			return $this->_permissions[$code];
		}
		if (isset($this->_permissions_names[$code]) && isset($this->_permissions[$this->_permissions_names[$code]])) {
			return $this->_permissions[$this->_permissions_names[$code]];
		}
		return false;
	}
}