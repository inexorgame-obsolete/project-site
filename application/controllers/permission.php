<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Permission extends CI_Controller {

	// The user object
	public $user = false;

	// Does the user have the permission to view the general permissions
	public $has_perm = false;

	/**
	 * Magic Method __construct()
	 */
	public function __construct() {
		parent::__construct();
		$this->load->model('shared/permissions_model');
		$this->load->model('shared/pgroups_model');
		$this->load->library('auth');
		$this->load->library('permissions');
		$this->load->library('template');
		$this->load->database();
		$this->user = $this->auth->user();
		if(!$this->user) return;
		$this->permissions->set_user($this->user->id);
		$this->has_perm = $this->permissions->has_user_permission('edit_permissions');
		$this->template->add_css('tables');
	}

	/**
	 * Index file, redirects to the user profiles (via profile link) and to the group managment; Explains how the system works
	 */
	public function index() {
		$data['form'] = array(
			'input'  => array('type' => 'text', 'name' => 'link', 'placeholder' => 'Profile link...'),
			'submit' => array('type' => 'submit', 'name' => 'submit', 'value' => 'Edit permissions') 
		);
		$data['error'] = false;
		if(isset($_POST['submit'])) {
			if(preg_match('/\/user\/(?:edit\/)?(\d*)/i', $_POST['link'], $matches)) {
				if(isint($matches[1]))
				{
					if($this->auth->user($matches[1]))
					{
						redirect('/permission/user/' . $matches[1]);
					} else {
						$data['error'] = 'No user with this URL exists.';
					}
				} else {
					$data['error'] = 'The URL is not a valid user-URL.';
				}
			} else {
				$data['error'] = 'The URL is not a valid user-URL.';
			}
		}
		$this->load->view('permissions/index', $data);
	}

	/**
	 * The groups of a user; Allows to add or remove from groups
	 * @param int $id id of the user
	 * @param string $order DESC|ASC; Order of the group-names
	 */
	public function user_groups($id = false, $order = 'DESC')
	{
		if($this->permissions->has_user_permission('edit_permissions', $id) && !$this->permissions->has_user_permission('edit_permission_editors_permissions'))
		{
			$this->template->render_permission_error(NULL, 'You have not the permission to edit this user. You need the permission \'edit_permission_editors_permissions\' therefore.');
			return;
		}
		$this->load->model('shared/users_pgroups_model');
		if(strtolower($order) != 'asc') $order = 'DESC';
		if($id === false || !isint($id)) { show_404(); return; }
		isint($start, 1);
		isint($results, 30);
		$data = array();
		$data['form'] = false;
		$data['order'] = $order;
		$data['errors'] = false;
		$data['add_group_error'] = false;
		$data['user'] = $this->auth->user($id);
		if($this->permissions->has_user_permission('edit_users_permission_groups') && isset($_POST['submit_add_group'])) {
			$data['add_group_error'] = $this->permissions->add_user_to_group($id, $_POST['group']);
		}
		if($this->permissions->has_user_permission('edit_users_permission_groups') && isset($_POST['remove']) && isint($_POST['remove'])) $this->users_pgroups_model->remove($_POST['remove']);
		$data['groups'] = $this->permissions->get_users_groups($id);
		if($this->permissions->has_user_permission('edit_users_permission_groups')){ 
			if(isset($_POST['submit'])) $data['errors'] = $this->_update_users_groups($data['groups'], $id);
			$this->_create_users_groups_form($data['form'], $data['groups']);
			$this->_create_add_user_to_group_form($data['add_form']);
		}
		$this->load->view('permissions/user_groups', $data);
	}

	/**
	 * The permissions of a user
	 * @param int $id id of the user
	 * @param int $start pagination start
	 * @param int $results results per site
	 */
	public function user_permissions($id = false, $start = 1, $results = 30) {
		if($id === false) { show_404(); return; }
		$data = array();
		$data['user'] = $this->auth->user($id);
		if($this->permissions->has_user_permission('edit_permissions', $id) && !$this->permissions->has_user_permission('edit_permission_editors_permissions'))
		{
			$this->template->render_permission_error(NULL, 'You have not the permission to edit this user. You need the permission \'edit_permission_editors_permissions\' therefore.');
			return;
		}
		if(!isint($start)) {
			// Check if a specific permission is set to display just this permission. This permission would be identified because $start has to be a string, not an int.
			if($permission = $this->permissions_model->get_permission_by_name($start))
			{
				if($results === false) $results = 1;
				return $this->_user_permission($id, $permission);
			}
			show_404();
			return;
		}
		$this->load->model('shared/users_permissions_model');
		isint($start, 1);
		isint($results, 30);
		$data['start'] = $start;
		$data['results'] = $results;
		$start = ($start - 1) * $results;
		if(isset($_POST['submit']) && $data['edit_permissions']) $this->_update_user_permissions($id);
		$data['permissions'] = $this->permissions_model->get_permissions(false, $start, $results);
		$data['edit_permissions'] = $this->permissions->has_user_permission('edit_permissions');
		foreach($data['permissions'] as $i => $p)
		{
			$data['permissions'][$i] = $p;
			$data['permissions'][$i]->has_permission = $this->permissions->has_user_permission($p->id, $id, $data['permissions'][$i]->level);
			$data['permissions'][$i]->has_childrens = $this->permissions_model->has_childrens($p->id);
		}
		if($data['edit_permissions']) $this->_create_user_permission_inputs($data['form'], $data['permissions']);
		$data['max_pagination'] = $this->permissions_model->max_pagination($results);
		$data['id'] = $id;
		$this->load->view('permissions/user_permissions', $data);
	}

	/**
	 * Lists all groups
	 * @param int $start start of pagination
	 * @param int $results results per site
	 */
	public function list_groups($start = 1, $results = 30)
	{
		$data = array();
		$data['add_group'] = false;
		$this->_search_field($data['search']);
		if(isset($_POST[$data['search']['submit']['name']]))
		{
			redirect('permission/groups/' . urlencode($_POST[$data['search']['search']['name']]) . '/' . urlencode($_POST[$data['search']['start']['name']]) . '/' . urlencode($_POST[$data['search']['limit']['name']]) . '/');
		}
		if(!isint($results)) $results = 30;
		$start = ($start - 1) * $results;
		$data['groups'] = $this->pgroups_model->get_groups($start, $results);
		$data['start'] = $start / $results + 1;
		$data['results'] = $results;
		if($this->permissions->has_user_permission('add_permission_groups')) $this->_create_group_form($data['add_group']);
		if($this->permissions->has_user_permission('add_permission_groups') && isset($_POST['add_group'])) {
			
			if(str_replace(' ', '', $_POST['groupname']) == '') $data['add_group_error'] = 'Please enter a name for the group';
			if($this->pgroups_model->get_group_by_name($_POST['groupname']) !== false) $data['add_group_error'] = 'The name already exists. Please enter another one.';

			if(!isset($data['add_group_error'])) {
				$this->pgroups_model->add_group($_POST['groupname'], $_POST['description']);
				$group = $this->pgroups_model->get_group_by_name($_POST['groupname']);
				redirect('permission/group/' . $group->id);
			}
		}
		$data['max_pagination'] = $this->pgroups_model->max_pagination($results);
		$this->load->view('permissions/list_groups', $data);
	}

	/**
	 * Groups search
	 * @param string $search search-string
	 * @param int $start start of pagination
	 * @param int $results results per site
	 */
	public function search_groups($search, $start = 1, $results = 30)
	{
		$data = array();
		$this->_search_field($data['search'], $search, $start, $results);
		if($start < 1) $start = 1; 
		if(!isint($results)) $results = 30;
		$start = ($start - 1) * $results;
		$data['groups'] = $this->pgroups_model->search_groups($search, $start, $results);
		$data['start'] = $start / $results + 1;
		$data['results'] = $results;
		$data['searchstring'] = $search;
		if($this->permissions->has_user_permission('add_permission_groups')) $this->_create_group_form($data['add_group']);
		if($this->permissions->has_user_permission('add_permission_groups') && isset($_POST['add_group'])) {
			
			if(str_replace(' ', '', $_POST['groupname']) == '') $data['add_group_error'] = 'Please enter a name for the group';
			if($this->pgroups_model->get_group_by_name($_POST['groupname']) !== false) $data['add_group_error'] = 'The name already exists. Please enter another one.';

			if(!isset($data['add_group_error'])) {
				$this->pgroups_model->add_group($_POST['groupname'], $_POST['description']);
				$group = $this->pgroups_model->get_group_by_name($_POST['groupname']);
				redirect('permission/group/' . $group->id);
			}
		}
		$data['max_pagination'] = $this->pgroups_model->search_max_pagination($search, $results);
		$this->load->view('permissions/search_groups', $data);
	}

	/**
	 * Group-permissions
	 * @param int $id id of the group
	 * @param int $start start of the pagination of permissions
	 * @param mixed $results IF INT: results per page IF STRING: The permission-view for this group on this specific permission to edit permission-childs 
	 */
	public function group($id = false, $start = 1, $results = false)
	{
		if($id == false) { show_404(); return; }
		if(!isint($start)) {
			// Check if a specific permission is set to display just this permission. This permission would be identified because $start has to be a string, not an int.
			if($permission = $this->permissions_model->get_permission_by_name($start))
			{
				if($results === false) $results = 1;
				return $this->_group_permission($id, $permission);
			}
			show_404();
			return;
		}
		if($start < 1) $start = 1;
		if($results === false) $results = 30;
		if(!isint($results)) $results = 30;
		$this->load->model('shared/pgroups_permissions_model');
		$data = array();
		$data['edit_permission_groups'] = $this->permissions->has_user_permission('edit_permission_groups');
		$data['start'] = $start;
		$data['results'] = $results;
		$start = ($start - 1) * $results;
		$data['group'] = $this->pgroups_model->get_group($id);
		if(!$data['group']) return show_404();
		if(isset($_POST['submit']) && $data['edit_permission_groups']) $this->_update_permissions($id);
		$data['permissions'] = $this->permissions_model->get_permissions(false, $start, $results);
		foreach($data['permissions'] as $i => $p)
		{
			$data['permissions'][$i] = $p;
			$data['permissions'][$i]->has_permission = $this->pgroups_permissions_model->has_group_permission($id, $p->id);
			$data['permissions'][$i]->has_childrens = $this->permissions_model->has_childrens($p->id);
		}
		$data['id'] = $id;
		$data['max_pagination'] = $this->permissions_model->max_pagination($results);
		if($data['edit_permission_groups']) $this->_create_permission_inputs($data['form'], $data['permissions']);
		$this->load->view('permissions/group', $data);
	}

	/**
	 * The permission-view for this group on this specific permission to edit permission-childs 
	 * @param int $groupid id of the group to be viewed
	 * @param object $permission the permissions-object of the parent
	 */
	private function _group_permission($groupid, $permission) {
		$this->load->model('shared/pgroups_permissions_model');
		$data = array();
		$data['form'] = false;
		$data['edit_permission_groups'] = $this->permissions->has_user_permission('edit_permission_groups');
		if(isset($_POST['submit']) && $data['edit_permission_groups']) $this->_update_permissions($groupid);
		$data['group'] = $this->pgroups_model->get_group($groupid);
		$data['permissions'] = $this->permissions_model->get_permissions_recursive($permission);
		$this->_add_has_permission_recursive($data['permissions'], $groupid);
		if($data['edit_permission_groups'])	$this->_create_recursive_permission_inputs($data['form'], $data['permissions'], $groupid);

		$this->load->view('permissions/group_permission', $data);
	}

	/**
	 * The permission-view for this user on this specific permission to edit the permission-childs
	 * @param int $userid id of the user
	 * @param object $permission ther permissions-parent of the parent
	 */
	private function _user_permission($userid, $permission) {
		$this->load->model('shared/users_permissions_model');
		$data = array();
		$data['form'] = false;
		$data['edit_permissions'] = $this->permissions->has_user_permission('edit_permissions');
		if(isset($_POST['submit']) && $data['edit_permissions']) $this->_update_user_permissions($userid);
		$data['user'] = $this->auth->user($userid);
		$data['permissions'] = $this->permissions_model->get_permissions_recursive($permission);
		$this->_add_has_user_permission_recursive($data['permissions'], $userid);
		if($data['edit_permissions']) $this->_create_recursive_user_permission_inputs($data['form'], $data['permissions'], $userid);
		$this->load->view('permissions/user_permission', $data);
	}

	/**
	 * Creates form for updating users pgroups
	 * @param array $groups the groups-array of the user
	 * @param int $userid id of the user
	 * @return array returns error-array including messages if something failed.
	 */
	private function _update_users_groups(&$groups, $userid) {
		$gs = $groups;
		$sigs = array();
		$errors = array();
		foreach($gs as $g) {
			// If checkboxes instead of buttons:
			// if(isset($_POST['remove_' . $g->id])) $this->users_pgroups_model->remove_user_from_group($userid, $g->pgroup_id);
			if(isint($_POST['significance_' . $g->id]))
			{
				$g->significance = $_POST['significance_' . $g->id];
				$sigs[$_POST['significance_' . $g->id]][] = $g;
			} else {
				$sigs[(int) $g->significance][] = $g;
			}
		}
		$i = 0;
		$errors = array('messages' => array(), 'relateds' => array());
		foreach($sigs as $i => $s) {
			if(count($s) > 1) {
				$groupcount = count($s) - 1;
				$errors['messages'][$i] = '';
				foreach($s as $gi => $g) {
					if($gi == $groupcount) $errors['messages'][$i] .= ' and \'' . $g->name . '\'';
					else $errors['messages'][$i] .= ', \'' . $g->name . '\'';
					$errors['relateds'][$g->id] = true;
				}
				$errors['messages'][$i] = substr($errors['messages'][$i], 2);
				$i++;
			} else {
				$this->users_pgroups_model->change_significance($userid, $s[0]->group_id, $s[0]->significance);
			}
		}
		$groups = $this->permissions->get_users_groups($userid);
		if(count($errors['messages']) == 0) return false;
		return $errors;
	}

	/**
	 * Checks via checking the form if the user has the permissions and updates the data of the specified group.
	 * @param int $groupid The groupid to update
	 */
	private function _update_permissions($groupid) {
		$i = 0;
		while(isset($_POST['pointer_' . $i]) && isint($_POST['pointer_' . $i])) {
			$pid = $_POST['pointer_' . $i];
			if($this->input->post('default_' . $pid)) {
				$this->pgroups_permissions_model->delete($groupid, $pid);
			} else {
				if($this->input->post('permission_' . $pid)) {
					$v = $this->pgroups_permissions_model->exists($groupid, $pid);
					if(isset($v->value) && $v->value == 0)
					{
						$this->pgroups_permissions_model->update($groupid, $pid, true);
					} elseif(!isset($v->value)) $this->pgroups_permissions_model->insert($groupid, $pid, true);
				} else {
					$v = $this->pgroups_permissions_model->exists($groupid, $pid);
					if(isset($v->value) && $v->value == 1)
					{
						$this->pgroups_permissions_model->update($groupid, $pid, false);
					} elseif(!isset($v->value)) $this->pgroups_permissions_model->insert($groupid, $pid, false);
				}
			}
			$i++;
		}
	}

	/**
	 * Checks via checking the form if the user has the permissions and updates the data of the specified user.
	 * @param int $userid The userid to update
	 */
	private function _update_user_permissions($userid) {
		$i = 0;
		while(isset($_POST['pointer_' . $i]) && isint($_POST['pointer_' . $i])) {
			$pid = $_POST['pointer_' . $i];
			if($this->input->post('level_' . $pid)) {
				$this->users_permissions_model->delete($userid, $pid);
			} else {
				if($this->input->post('permission_' . $pid)) {
					$v = $this->users_permissions_model->exists($userid, $pid);
					if(isset($v->value) && $v->value == 0)
					{
						$this->users_permissions_model->update($userid, $pid, true);
					} elseif(!isset($v->value)) $this->users_permissions_model->insert($userid, $pid, true);
				} else {
					$v = $this->users_permissions_model->exists($userid, $pid);
					if(isset($v->value) && $v->value == 1)
					{
						$this->users_permissions_model->update($userid, $pid, false);
					} elseif(!isset($v->value)) $this->users_permissions_model->insert($userid, $pid, false);
				}
			}
			$i++;
		}
	}

	/**
	 * Adds to an array if the user has the permission for the group specified in the same group.
	 * @param array &$data Data array containing the permission-id
	 * @param int $id userid
	 */
	private function _add_has_permission_recursive(&$data, $id)
	{
		$data['has_permission'] = $this->pgroups_permissions_model->has_group_permission($id, $data['id']);
		if($data['childs'])
			foreach($data['childs'] as $i => $c)
			{
				$this->_add_has_permission_recursive($data['childs'][$i], $id);
			}
	}

	/**
	 * Adds to an array if the user has the permission.
	 * @param array &$data Data array containing the permission-id
	 * @param int $id userid
	 */
	private function _add_has_user_permission_recursive(&$data, $id)
	{
		$data['has_permission'] = $this->permissions->has_user_specific_permission($data['id'], $id, $data['level']);
		if($data['childs'])
			foreach($data['childs'] as $i => $c)
			{
				$this->_add_has_user_permission_recursive($data['childs'][$i], $id);
			}
	}

	/**
	 * Remapper
	 * @param string $method Method to be called
	 * @param array $params array of parameters
	 */
	public function _remap($method, $params = array())
	{
		if(!$this->has_perm) return $this->template->render_permission_error();
		if($method == 'groups' && (!isset($params[0]) || isint($params[0])))
		{
			return call_user_func_array(array($this, 'list_groups'), $params);
		}
		elseif($method == 'groups')
		{
			return call_user_func_array(array($this, 'search_groups'), $params);
		}
		elseif($method == 'user')
		{
			if(isset($params[1])) {
				if($params[1] == 'groups') {
					unset($params[1]);
					return call_user_func_array(array($this, 'user_groups'), $params);
				}

				if($params[1] == 'permissions') unset($params[1]);
			}
			return call_user_func_array(array($this, 'user_permissions'), $params);
		}
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}

	/**
	 * Adds groupform to data array
	 * @param array $data the array to be added
	 */
	private function _create_group_form(&$data) {
		$data['name_label'] = array(
			'content' => 'Name',
			'for' => 'create_group_name'
		);
		$data['description_label'] = array(
			'content' => 'Description',
			'for' => 'create_group_description',
			'class' => 'textarea-label'
		);
		$data['name'] = array(
			'id' => 'create_group_name',
			'type' => 'text',
			'name' => 'groupname',
			'value' => $this->input->post('groupname')
		);
		$data['description'] = array(
			'id' => 'create_group_description',
			'name' => 'description',
			'value' => $this->input->post('description')
		);
		$data['submit'] = array(
			'class' => 'centered',
			'name' => 'add_group',
			'type' => 'submit',
			'value' => 'Add group'
		);
	}

	/**
	 * Adds the searchform to the data array
	 * @param array &$data the data array to add the form to
	 * @param string $value the current value
	 * @param int $start Pagination-start
	 * @param int $limit results per site
	 */
	private function _search_field(&$data, $value = '', $start = 1, $limit = 30) {
		$data['search'] = array(
			'type'  => 'text',
			'name'  => 'search',
		);
		$data['search']['value'] = $value;
		$data['submit'] = array(
			'type'	=> 'submit',
			'name'	=> 'submit',
			'value' => 'Search',
		);
		$data['start'] = array(
			'type'  => 'hidden',
			'name'  => 'start',
			'value' => $start
		);
		$data['limit'] = array(
			'type'  => 'hidden',
			'name'  => 'limit',
			'value' => $limit
		);
	}

	/**
	 * Creates recursive the inputs to edit the permissions for one group
	 * @param array &$data the data to add the form-data to
	 * @param array $permissions recursive array of permission containing the childs
	 * @param int $groupid the group id to edit the permissions for
	 * @param int &$i control variable through recursion so every permission has it own id and the id's are starting from 0
	 */
	private function _create_recursive_permission_inputs(&$data, $permissions, $groupid, &$i = 0)
	{
		$data['pointers'][$permissions['id']] = array(
			'type' => 'hidden',
			'name' => 'pointer_' . $i,
			'value' => $permissions['id']
		);
		$data['permissions'][$permissions['id']] = array(
			'type' => 'checkbox',
			'name' => 'permission_' . $permissions['id'],
			'id' => 'e_p_' . $i,
			'value' => '1'
		);
		$data['defaults'][$permissions['id']] = array(
			'type' => 'checkbox',
			'name' => 'default_' . $permissions['id'],
			'title' => 'use the permissions default configuration',
			'id' => 'e_d_' . $i,
			'value' => '1'
		);
		$data['labels']['permissions'][$permissions['id']] = array(
			'for' => 'e_p_' . $i,
			'title' => 'has this permission'
		);
		$data['labels']['defaults'][$permissions['id']] = array(
			'for' => 'e_d_' . $i,
			'title' => 'reset to default'
		);
		$data['submit'] = array(
			'type' => 'submit',
			'name' => 'submit',
			'class' => 'full-width',
			'value' => 'Save Changes'
		);
		if($permissions['has_permission'] === true) {
			$data['permissions'][$permissions['id']]['checked'] = 'checked';
		} elseif($permissions['has_permission'] === 0) {
			if($permissions['default'] == true)
			{
				$data['permissions'][$permissions['id']]['checked'] = 'checked';
				$data['permissions'][$permissions['id']]['title'] = 'by default';
			}
			$data['defaults'][$permissions['id']]['checked'] = 'checked';
		}

		$i++;
		if($permissions['childs']) {
			foreach($permissions['childs'] as $index => $c) {
				$this->_create_recursive_permission_inputs($data, $c, $groupid, $i);
			}
		}
	}

	/**
	 * Creates recursive the inputs to edit the permissions for one user
	 * @param array &$data the data to add the form-data to
	 * @param array $permissions recursive array of permission containing the childs
	 * @param int $groupid the group id to edit the permissions for
	 * @param int &$i control variable through recursion so every permission has it own id and the id's are starting from 0
	 */
	private function _create_recursive_user_permission_inputs(&$data, $permissions, $groupid, &$i = 0)
	{
		$data['pointers'][$permissions['id']] = array(
			'type' => 'hidden',
			'name' => 'pointer_' . $i,
			'value' => $permissions['id']
		);
		$data['permissions'][$permissions['id']] = array(
			'type' => 'checkbox',
			'name' => 'permission_' . $permissions['id'],
			'id' => 'e_p_' . $i,
			'value' => '1'
		);
		$data['levels'][$permissions['id']] = array(
			'type' => 'checkbox',
			'name' => 'level_' . $permissions['id'],
			'title' => 'Use lower level. Current: ',
			'id' => 'e_d_' . $i,
			'value' => '1'
		);
		$data['labels']['permissions'][$permissions['id']] = array(
			'for' => 'e_p_' . $i,
			'title' => 'has this permission'
		);
		$data['submit'] = array(
			'type' => 'submit',
			'name' => 'submit',
			'class' => 'full-width',
			'value' => 'Save Changes'
		);
		if($permissions['has_permission'] == true) {
			$data['permissions'][$permissions['id']]['checked'] = 'checked';
		}
		if($permissions['level'] == 'g' || $permissions['level'] == 'd') {
			$data['levels'][$permissions['id']]['checked'] = 'checked';
			if($permissions['level'] == 'g') $data['levels'][$permissions['id']]['title'] .= 'group.';
			else $data['levels'][$permissions['id']]['title'] .= 'default.';
		} else
			$data['levels'][$permissions['id']]['title'] .= 'user.';


		$data['labels']['levels'][$permissions['id']] = array(
			'for' => 'e_d_' . $i,
			'title' => $data['levels'][$permissions['id']]['title']
		);

		$i++;
		if($permissions['childs']) {
			foreach($permissions['childs'] as $index => $c) {
				$this->_create_recursive_user_permission_inputs($data, $c, $groupid, $i);
			}
		}
	}

	/**
	 * Create permisison inputs for editing multiple parent-permissions at once for a group
	 * @param array &$data the array to add the form data to
	 * @param array $permissions containing permissions which could be edited
	 */
	private function _create_permission_inputs(&$data, $permissions) {
		$i = 0;
		foreach($permissions as $p)
		{
			$data['pointers'][$i] = array(
				'type'  => 'hidden',
				'name'  => 'pointer_' . $i,
				'value' => $p->id
			);
			$data['permissions'][$i] = array(
				'type' => 'checkbox',
				'value' => '1',
				'name' => 'permission_' . $p->id
			);
			$data['defaults'][$i] = array(
				'type' => 'checkbox',
				'name' => 'default_' . $p->id,
				'value' => '1',
				'title' => 'use the permissions default configuration'
			);
			if($p->has_permission === true) $data['permissions'][$i]['checked'] = 'checked';
			elseif($p->has_permission === 0 && $p->default == true) {
				$data['permissions'][$i]['checked'] = 'checked';
				$data['permissions'][$i]['title'] = 'by default';
			}
			if($p->has_permission === 0) $data['defaults'][$i]['checked'] = 'checked';
			$i++;
		}
		$data['submit'] = array(
			'type' => 'submit',
			'name' => 'submit',
			'class' => 'full-width',
			'value' => 'Save Changes'
		);
	}

	/**
	 * Create permisison inputs for editing multiple parent-permissions at once for a user
	 * @param array &$data the array to add the form data to
	 * @param array $permissions containing permissions which could be edited
	 */
	private function _create_user_permission_inputs(&$data, $permissions) {
		$i = 0;
		foreach($permissions as $p)
		{
			$data['pointers'][$p->id] = array(
				'type'  => 'hidden',
				'name'  => 'pointer_' . $i,
				'value' => $p->id
			);
			$data['permissions'][$p->id] = array(
				'type' => 'checkbox',
				'value' => '1',
				'name' => 'permission_' . $p->id
			);
			$data['level'][$p->id] = array(
				'type' => 'checkbox',
				'name' => 'level_' . $p->id,
				'value' => '1'
			);
			
			if($p->level == 'd' || $p->level == 'g') $data['level'][$p->id]['checked'] = 'checked';
			
			if($p->level == 'd') $data['level'][$p->id]['title'] = 'Current level: default';
			elseif($p->level == 'g') $data['level'][$p->id]['title'] = 'Current level: group';
			else $data['level'][$p->id]['title'] = 'Current level: user';

			if($p->has_permission === true) $data['permissions'][$p->id]['checked'] = 'checked';
			$i++;
		}
		$data['submit'] = array(
			'type' => 'submit',
			'name' => 'submit',
			'class' => 'full-width',
			'value' => 'Save Changes'
		);
	}

	/**
	 * Adds form for editing a users groups
	 * @param array &$data the array to add the form-data to
	 * @param array $permissions array of permissions so
	 * @return type
	 */
	private function _create_users_groups_form(&$data, $permissions)
	{
		$i = 0;
		$data['submit'] = array(
			'type'  => 'submit',
			'name'  => 'submit',
			'value' => 'Save Changes',
			'class' => 'full-width'
		);
		if(!is_array($data)) $data = array();
		foreach($permissions as $p) {
			$data['pointer'][$p->id] = array(
				'type' => 'hidden',
				'name' => 'pointer_' . $i,
				'value' => $p->id
			);
			$data['significance'][$p->id] = array(
				'name'  => 'significance_' . $p->id,
				'type'  => 'number',
				'min'   => '0',
				'size'  => '4',
				'step'  => '1',
				'value' => (isset($_POST['significance_' . $p->id])) ? $_POST['significance_' . $p->id] : (int) $p->significance
			);
			$data['remove'][$p->id] = array(
				'name'    => 'remove',
				'type'    => 'submit',
				'content' => 'R',
				'title'   => 'Remove user from this groups.',
				'value'   => $p->id
			);
			$i++;
		}
	}

	/**
	 * Adds form for adding a user to a group
	 * @param array &$data adds the form-data to the array
	 */
	private function _create_add_user_to_group_form(&$data)
	{
		$data['submit'] = array(
			'type' => 'submit',
			'name' => 'submit_add_group',
			'value' => 'Add to group'
		);
		$data['group'] = array(
			'type' => 'text',
			'name' => 'group',
			'placeholder' => 'Group name...'
		);
	}
}