<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Permission extends CI_Controller {
	public $user = false;
	public $has_perm = false;
	public function __construct() {
		parent::__construct();
		$this->load->model('permissions_model');
		$this->load->model('pgroups_model');
		$this->load->library('auth');
		$this->load->library('permissions');
		$this->load->library('template');
		$this->load->database();
		$this->user = $this->auth->user();
		if(!$this->user) return;
		$this->permissions->set_user($this->user->id);
		$this->has_perm = $this->permissions->has_user_permission('edit_permissions');
	}

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

	public function group($id = false, $start = 1, $results = false, $sub_results = 30)
	{
		if($id == false) { show_404(); return; }
		if(!isint($start)) {
			// Check if a specific permission is set to display just this permission. This permission would be identified because $start has to be a string, not an int.
			if($permission = $this->permissions_model->get_permission_by_name($start))
			{
				if($results === false) $results = 1;
				return $this->_group_permission($id, $permission, $results, $sub_results);
			}
			show_404();
			return;
		}
		if($start < 1) $start = 1;
		if($results === false) $results = $sub_results;
		if(!isint($results)) $results = 30;
		$this->load->model('pgroups_permissions_model');
		$data = array();
		$data['edit_permission_groups'] = $this->permissions->has_user_permission('edit_permission_groups');
		$data['start'] = $start;
		$data['results'] = $results;
		$start = ($start - 1) * $results;
		$data['group'] = $this->pgroups_model->get_group($id);
		if(!$data['group']) return show_404();
		if(isset($_POST['submit']) && $data['edit_permission_groups']) $this->_update_permissions($id);
		$data['permissions'] = $this->permissions_model->get_permissions(true, $start, $results);
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

	private function _group_permission($groupid, $permission, $start, $results) {
		$this->load->model('pgroups_permissions_model');
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

	private function _update_permissions($groupid) {
		$i = 0;
		while(true) {
			if(!isset($_POST['pointer_' . $i]) || !isint($_POST['pointer_' . $i])) break;
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

	private function _add_has_permission_recursive(&$data, $id)
	{
		$data['has_permission'] = $this->pgroups_permissions_model->has_group_permission($id, $data['id']);
		if($data['childs'])
			foreach($data['childs'] as $i => $c)
			{
				$this->_add_has_permission_recursive($data['childs'][$i], $id);
			}
	}

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
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}


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
}