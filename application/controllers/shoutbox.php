<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Shoutbox extends CI_Controller {
	function __construct() 
	{
		parent::__construct();
		$this->load->library('ion_auth');
		$this->load->library('form_validation');
		$this->load->helper('url');
		$this->load->helper('template');
		$this->lang->load('auth');
		$this->load->helper('language');
		$this->load->helper('captcha');
		$this->load->model('shoutbox_model');
	}

	public function index()
	{


	}

	public function view($id, $site = 1)
	{
		$shoutbox = $this->shoutbox_model->get_shoutbox_info($id);
		$permissions = $this->shoutbox_model->user_shoutbox_permissions($id);
		if($permissions == false)
		{
			show_404();
		}
		if($permissions != false)
		{
			$user = $this->ion_auth->user()->row();
			if ($user) $data['user'] = $user; 
			else $data['user'] = FALSE;

			$data['permissions'] = $permissions;
			if($site>0)
				$site--; // Site should not start with 0, but is needed for database
			else
				$site = 0;


			

			$this->form_validation->set_rules('shoutbox_text', 'shout', 'xss_clean|required');
			if($this->form_validation->run() == true && $permissions == 'write') {
				$this->shoutbox_model->submit($id, $this->input->post('shoutbox_text'), $user->id);
				$data['form']['validation_message'] = '<p>Shout submitted</p>';
			} 
			else 
			{
				$data['form']['validation_message'] = validation_errors();
			}
			$data['form']['text'] = array(
				'name'  => 'shoutbox_text',
				'placeholder' => 'Your shout...',
			);
			if(!$this->form_validation->run())
			{
				$data['form']['text']['value'] = $this->form_validation->set_value('shoutbox_text');
			}
			$data['form']['submit'] = array(
				'name'        => 'shoutbox_submit',
				'value'       => 'Send'
			);



			$data['info'] = $shoutbox;
			$data['creator'] = $this->ion_auth->user($data['info']->creator_id)->row();
			$data['shouts'] = $this->shoutbox_model->get_shouts($id, $site);
			$authors = $this->shoutbox_model->get_authors($id);
			foreach($authors as $a)
			{
				$data['authors'][$a] = $this->ion_auth->user($a)->row();
			}

			$this->load->view('shoutbox/shoutbox', $data);
		}
	}

	public function _remap($method, $params = array()) {
		if(method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		} 
		elseif((int) $method == $method)
		{
			$params['id'] = $method;
			return call_user_func_array(array($this, 'view'), $params);
		}
		else
		{
			show_404();
		}
	}
}