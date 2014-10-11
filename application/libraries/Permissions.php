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

	public function has_user_permission($permission, $id = false, &$by = false) {
		$by = 'u';
		if(!isint($id)) $id = $this->_userid;
		if($id != false) {
			$permission = $this->_get_permission($permission);
			if(isset($this->_user_permissions[$id][$permission->id])) {
				$by = $this->_user_permissions[$id][$permission->id]['by'];
				return $this->_user_permissions[$id][$permission->id]['value'];
			}
			$required_permissions = $this->get_all_parents($permission->id);
			$required_permissions[] = (int) $permission->id;

			// Check if permissions are directly assigned to user
			foreach($required_permissions as $i => $p) {
				$has_user_permission = $this->_CI->users_permissions_model->has_user_permission($id, $p);
				if($has_user_permission === false) { 
					$this->_user_permissions[$id][$p]['value'] = false;
					$this->_user_permissions[$id][$p]['by'] = $by;
					$this->_user_permissions[$id][$permission->id]['value'] = false;
					$this->_user_permissions[$id][$permission->id]['by'] = $by;
					return false; 
				}
				elseif($has_user_permission === true) 
				{
					$this->_user_permissions[$id][$p]['value'] = true;
					$this->_user_permissions[$id][$p]['by'] = $by;
					unset($required_permissions[$i]);
				}
			}
			if(count($required_permissions) == 0) 
			{
				$this->_user_permissions[$id][$permission->id]['value'] = true;
				$this->_user_permissions[$id][$permission->id]['by'] = $by;
				return true;
			}

			// Check if permissions are assigned to group and fit the rules
			$by = 'g';
			$required_permissions = $this->_CI->pgroups_permissions_model->groupset_have_permissions(
				$this->_CI->users_pgroups_model->user_group_ids($id),
				$required_permissions,
				$this->_group_permissions
			);
			if(is_bool($required_permissions)) 
			{
				$this->_user_permissions[$id][$permission->id]['value'] = $required_permissions;
				$this->_user_permissions[$id][$permission->id]['by'] = $by;
				return $required_permissions;
			}

			// Use default $permissions
			$by = 'd';
			foreach($required_permissions as $p) {
				if($this->_get_permission($p)->default == false) 
				{
					$this->_user_permissions[$id][$permission->id]['value'] = false;
					$this->_user_permissions[$id][$permission->id]['by'] = $by;
					return false;
				}
			}

			$this->_user_permissions[$id][$permission->id]['value'] = true;
			$this->_user_permissions[$id][$permission->id]['by'] = $by;
			return true;
		}

		$this->_user_permissions[$id][$permission->id]['value'] = false;
		$this->_user_permissions[$id][$permission->id]['by'] = $by;
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

	public function has_user_specific_permission($permission, $id = false, &$by = false)
	{
		$permission = $this->_get_permission($permission);
		isint($id, $this->_userid);
		$by = 'u';
		$p = $this->_CI->users_permissions_model->has_user_permission($id, $permission->id);
		if(is_bool($p)) return $p;

		$by = 'g';
		$p = $this->_CI->pgroups_permissions_model->groupset_have_permissions(
			$this->_CI->users_pgroups_model->user_group_ids($id), 
			array($permission->id)
		);
		if(is_bool($p)) return $p;

		$by = 'd';
		return $permission->default;

	}

	public function get_users_groups($id = false, $order = 'DESC', $by = 'siginificance')
	{
		$return = $this->_CI->users_pgroups_model->user_groups($id);
		foreach ($return as $i => $v) {
			$g = $this->_CI->pgroups_model->get_group($v->group_id);
			$return[$i]->name = $g->name;
			$return[$i]->description = $g->description;
		}
		return $return;
	}

	public function add_user_to_group($userid, $groupname)
	{
		$g = $this->_CI->pgroups_model->get_group_by_name($groupname);
		if(!$g) return 'The group does not exist.';
		if($this->_CI->users_pgroups_model->is_user_in_group($userid, $g->id)) return 'The user is already in the group.';
		$this->_CI->users_pgroups_model->add_user_to_group($userid, $g->id);
		// Returns error message. So false means success.
		return false;
	}

	private function _get_permission($code) {
		if(isint($code)) $permission = $this->_CI->permissions_model->get_permission($code);
		else $permission = $this->_CI->permissions_model->get_permission_by_name($code); 
		if(count($permission) === 0) { return false; }
		return $permission;
	}
}