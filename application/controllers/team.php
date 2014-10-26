<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team extends CI_Controller {

	// Allowed project-types
	private $_allowed_types = array('website', 'data', 'main');

	/**
	 * Magic Method __construct()
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('development_model');
		$this->load->library("auth");
		$this->load->library("template");
		$this->load->library("permissions");
		$this->template->add_css($this);
	}


	/**
	 * Index site
	 */
	public function index($task = false, $id = false)
	{
		if(($task == 'done' || $task == 'undone') && isint($id))
		{
			$this->development_model->change_status($id, ($task == 'done') ? true : false);
		} 
		elseif ($task == 'delete' && isint($id))
		{
			$this->development_model->delete_task($id);
		}
		$user = $this->auth->user();	
		$data = array(
			"user"      => $user,
			"developer" => false,
			"devusers"  => array(),
			"form"      => array()
		);
		if($user) $this->permissions->set_user($user->id);
		$data['edit'] = $this->permissions->has_user_permission("developer_edit");
		$data['error'] = false;
		$this->_get_add_form($data['form']);
		if(isset($_POST['submit']) && $data['edit'] == true && in_array($_POST['type'], $this->_allowed_types))
		{
			$postlen = strlen($_POST['add_entry']);
			if($postlen > 512)
			{
				$data['form']['input']['value'] = $_POST['add_entry'];
				$data['error'] = 'The text may not have more than 512 characters.';
			} elseif($postlen < 1) {
				$data['error'] = 'The text has to have at least 1 character.';
			} else {
				$this->development_model->add_task($user->id, $_POST['add_entry'], $_POST['type']);
			}
		}

		$data['developer'] = $this->development_model->get();
		foreach($data['developer'] as $d)
		{
			foreach($d as $u => $dd)
			{
				$data["devusers"][$u] = $this->auth->user($u);
			}
		}

		$this->load->view('team/index', $data);
	}

	/**
	 * Adds new add-form to an array
	 * @param array $data The array to add the form-data to
	 */
	private function _get_add_form(&$data)
	{
		$data['input'] = array(
			'type'      => 'text',
			'name'      => 'add_entry',
			'maxlength' => 512
		);
		foreach($this->_allowed_types as $t)
		{
			$data['hidden_' . $t] = array(
				'type'  => 'hidden',
				'name'  => 'type',
				'value' => $t
			);
		}

		$data['submit'] = array(
			'type'  => 'submit',
			'name'  => 'submit',
			'value' => 'Add',
			'class' => 'one-line'
		);
	}

	/**
	 * Remaps so the method is the first argument
	 * @param string $method first argument
	 * @param array $arguments arguments after $method
	 */
	public function _remap($method, $arguments)
	{
		if(!is_array($arguments)) $arguments = array();
		array_unshift($arguments, $method);
		return call_user_func_array(array($this, 'index'), $arguments);
	}

}

/* End of file team.php */
/* Location: ./application/controllers/team.php */