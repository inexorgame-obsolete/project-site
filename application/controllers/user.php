<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {
	public $viewdata;

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
		$this->load->library('fileinfo');
		$this->load->config('ion_auth', TRUE);
		$this->load->library('template');
		$this->template = $this->template->get_instance();
	}

	function index()
	{
		// $this->ion_auth->limit(30);
		// $this->ion_auth->order_by('username', 'asc');
		// $users = $this->ion_auth->users()->result();
		// $data['users'] = $users;

		$this->form_validation->set_rules('search', 'search field', 'xss_clean|required');
		if ($this->form_validation->run() == true) {
			$search = $this->input->post('search');
			redirect('/user/search/' . urlencode($search));
		}
		$user = $this->ion_auth->user()->row();
		if($user) {
			redirect('/user/' . $user->id);
		} else {
			redirect('/user/register');
		}
	}


	function search($string = false)
	{
		$string = urldecode($string);
		
		$this->form_validation->set_rules('search', 'search field', 'xss_clean|required');
		if ($this->form_validation->run() == true) {
			redirect('/user/search/' . urlencode($this->input->post('search')));
		} else {
			$data['search_form']['validation_message'] = validation_errors();
		}
		$data['search_form']['search'] = array(
			'name'  => 'search',
			'type'  => 'text',
			'placeholder' => 'Search...',
			'value' => $string
		);
		$data['search_form']['submit'] = array(
			'name'  => 'submit',
			'value' => 'Search',
		);

		$this->ion_auth->limit(30);
		$this->ion_auth->like('username', $string);
		$this->ion_auth->like('first_name', $string);
		$this->ion_auth->like('last_name', $string);
		$users = $this->ion_auth->users()->result();
		$data['users'] = $users;

		$this->_render_page('user/list', $data);
	}

	function view($id = NULL)
	{
		$user = $this->ion_auth->user()->row();
		if(!$id) redirect('/user/view/' . $user->id, 'refresh');
		if(!$user || $id != $user->id) $view = $this->ion_auth->user($id)->row(); else $view = $user;
		if(!is_object($view))
		{
			show_404();
		}
		$unsets = array('password', 'salt', 'forgotten_password_code', 'forgotten_password_time', 'remember_code');
		$user = (array) $user;
		$view = (array) $view;
		foreach($unsets as $u) { unset($user[$u]); unset($view[$u]); }
		$data['user'] = $user;
		$data['view'] = $view;
		$userbackground = iimage($id, 'background');
		if($userbackground) $this->template->variable('eyecatcher_image', $userbackground, true);
		$this->_render_page('user/view_user', $data);
	}

	function register()
	{
		$this->data['title'] = "Create User";

		$tables = $this->config->item('tables','ion_auth');
		
		//validate form input
		$this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'xss_clean');
		$this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'xss_clean');
		$this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique['.$tables['users'].'.email]');
		$this->form_validation->set_rules('username', $this->lang->line('create_user_validation_username_label'), 'required|xss_clean|is_unique['.$tables['users'].'.username]');
		$this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
		$this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

		if ($this->form_validation->run() == true)
		{
			$username = $this->input->post('username');
			$email    = strtolower($this->input->post('email'));
			$password = $this->input->post('password');

			$additional_data = array(
				'first_name' => $this->input->post('first_name'),
				'last_name'  => $this->input->post('last_name'),
			);
		}
		if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data))
		{
			$this->_render_page('user/register_success', $this->data);
		}
		else
		{
			//display the create user form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			$this->data['first_name'] = array(
				'name'  => 'first_name',
				'id'    => 'first_name',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('first_name'),
			);
			$this->data['last_name'] = array(
				'name'  => 'last_name',
				'id'    => 'last_name',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('last_name'),
			);
			$this->data['username'] = array(
				'name'  => 'username',
				'id'    => 'username',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('username'),
			);
			$this->data['about'] = array(
				'name'	=> 'about',
				'id'	=> 'about',
				'type'	=> 'textarea',
				'value' => $this->form_validation->set_value('about')
			);
			$this->data['email'] = array(
				'name'  => 'email',
				'id'    => 'email',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('email'),
			);
			$this->data['password'] = array(
				'name'  => 'password',
				'id'    => 'password',
				'type'  => 'password',
				'value' => $this->form_validation->set_value('password'),
			);
			$this->data['password_confirm'] = array(
				'name'  => 'password_confirm',
				'id'    => 'password_confirm',
				'type'  => 'password',
				'value' => $this->form_validation->set_value('password_confirm'),
			);
			$this->data['solve_captcha'] = array(
				'name'  => 'solve_captcha',
				'id'    => 'solve_captcha',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('captcha'),
			);
			$chars['lower'] = "abcdefghijklmnopqrstuvwxyz";
			$chars['upper'] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$chars['numbers'] = "0123456789";
			foreach ($chars as $k => $v) { $part[$k] = substr(str_shuffle($v . $v . $v), 0, 3); }
			$this->data['captcha'] = array(
				'word' => str_shuffle($part['lower'] . $part['upper'] . $part['numbers']),
				'img_path' => FCPATH . 'data/captcha/',
				'img_url' => base_url('data/captcha') . '/',
			);
			$this->_render_page('user/register', $this->data);
		}
	}

	function edit($slug = false, $value = false, $ajax = false) {
		$user = $this->ion_auth->user()->row();
		if($user == false) {
			redirect('user/register');
			return;
		}
		if($slug == 'picture') {
			if(strtolower($value) == 'profile') $value = 'avatar';
			else $value = 'background';

			if($this->input->post('delete')) {
				if(file_exists(FCPATH . 'data/users/' . $value . '/' . $user->id . '.png')) unlink(FCPATH . 'data/users/' . $value . '/' . $user->id . '.png');
				if(file_exists(FCPATH . 'data/users/' . $value . '/' . $user->id . '.jpg')) unlink(FCPATH . 'data/users/' . $value . '/' . $user->id . '.jpg');
				$returnarray = array("success" => true, "action" => "delete", "type" => $value);
				if($value == 'avatar') $returnarray["path"] = base_url() . 'data/users/avatar/no-avatar.png';
				if($value == 'background') $returnarray["path"] = base_url() . 'data/users/images/eyecatcher.png';
			} else {
				if(isset($_FILES['picture'])) {
					$this->fileinfo->filepath = $_FILES['picture']['tmp_name'];
					if($this->fileinfo->is_file_type('jpg') == true) {
						move_uploaded_file($this->fileinfo->filepath, FCPATH . 'data/users/' . $value . '/' . $user->id . '.jpg');
						if(file_exists(FCPATH . 'data/users/' . $value . '/' . $user->id . '.png')) unlink(FCPATH . 'data/users/' . $value . '/' . $user->id . '.png');
						$returnarray = array("success" => true, "action" => "change", "path" => base_url() . 'data/users/' . $value . '/' . $user->id . '.jpg',  "type" => $value);
					} elseif($this->fileinfo->is_file_type('png') == true) {
						move_uploaded_file($this->fileinfo->filepath, FCPATH . 'data/users/' . $value . '/' . $user->id . '.png');
						if(file_exists(FCPATH . 'data/users/' . $value . '/' . $user->id . '.jpg')) unlink(FCPATH . 'data/users/' . $value . '/' . $user->id . '.jpg');
						$returnarray = array("success" => true, "action" => "change", "path" => base_url() . 'data/users/' . $value . '/' . $user->id . '.png', "type" => $value);
					} else {
						$returnarray = array("success" => false, "error" => "File signature does not fit png or jpg files.");
					}
				} else {
					$returnarray = array("success" => false, "error" => "No file selected.");
				}
			}

			if($ajax) 
			{
				$this->template->disable();
				$this->output->set_content_type('application/json')->set_output(json_encode($returnarray));
			} else {
				redirect('user/edit');
			}
		} 
		if($ajax != true) {
			$user = $this->ion_auth->user()->row();
			$data['user'] = $user;
			$data['edit_form']['username'] = array(
				'type' => 'text',
				'value' => $user->username,
				'autocomplete' => 'off',
				'name' => 'username',
				'id' => 'edit_username'
			);
			$data['edit_form']['email'] = array(
				'type' => 'email',
				'value' => $user->email,
				'autocomplete' => 'off',
				'name' => 'email',
				'id' => 'edit_email'
			);
			$data['edit_form']['first_name'] = array(
				'type' => 'text',
				'value' => $user->first_name,
				'autocomplete' => 'off',
				'name' => 'first_name',
				'id' => 'edit_first_name'
			);
			$data['edit_form']['last_name'] = array(
				'type' => 'text',
				'value' => $user->last_name,
				'autocomplete' => 'off',
				'name' => 'last_name',
				'id' => 'edit_last_name'
			);
			$data['edit_form']['password'] = array(
				'type' => 'password',
				'autocomplete' => 'off',
				'name' => 'password',
				'id' => 'password'
			);
			$data['edit_form']['password_verification'] = array(
				'type' => 'password',
				'autocomplete' => 'off',
				'name' => 'password_verification',
				'id' => 'password_verification',
				'placeholder' => 'Verification'
			);
			$data['edit_form']['old_password'] = array(
				'type' => 'password',
				'autocomplete' => 'off',
				'name' => 'old_password',
				'id' => 'old_password'
			);
			$data['edit_form']['about'] = array(
				'value' => $user->about,
				'autocomplete' => 'off',
				'name' => 'about',
				'id' => 'edit_about',
				'class' => 'about'
				);
			$data['edit_form']['submit'] = array(
				'value' => "Update information",
				'name' => 'submit',
				'type' => 'submit'
				);
			$data['change_picture']['profile']['upload'] = array(
				'type' => 'file',
				'name' => 'picture',
				);
			$data['change_picture']['profile']['submit'] = array(
				'type' => 'submit',
				'name' => 'submit',
				'value' => 'Change profile-picture'
				);
			$data['change_picture']['profile']['delete'] = array(
				'type' => 'submit',
				'name' => 'delete',
				'value' => 'Delete profile-picture'
				);
			$data['change_picture']['background']['upload'] = array(
				'type' => 'file',
				'name' => 'picture',
				);
			$data['change_picture']['background']['submit'] = array(
				'type' => 'submit',
				'name' => 'submit',
				'value' => 'Change background-picture'
				);
			$data['change_picture']['background']['delete'] = array(
				'type' => 'submit',
				'name' => 'delete',
				'value' => 'Delete background-picture'
				);

			if($this->input->post('submit'))
			{
				$error = array();
				$username 				= $this->input->post($data['edit_form']['username']['name']);
				$first_name 			= $this->input->post($data['edit_form']['first_name']['name']);
				$last_name 				= $this->input->post($data['edit_form']['last_name']['name']);
				$password				= $this->input->post($data['edit_form']['password']['name']);
				$password_verification 	= $this->input->post($data['edit_form']['password_verification']['name']);
				$about 					= $this->input->post($data['edit_form']['about']['name']);
				$ion_auth_config		= $this->config->item('ion_auth');

				$data['edit_form']['username']['value'] = $username;
				$data['edit_form']['first_name']['value'] 	= $first_name;
				$data['edit_form']['last_name']['value'] 	= $last_name;
				$data['edit_form']['about']['value'] 		= $about;
				if($this->ion_auth->hash_password_db($user->id, $this->input->post($data['edit_form']['old_password']['name']))) {

					if($this->ion_auth->username_check($username) && $user->username != $username) {
						$error[] = 'The username already exists.';
						unset($username);
					}
					if(strlen($username) > $ion_auth_config['max_username_length']) {
						$error[] = 'The username is too long. The username may contain ' . $this->config->item('ion_auth')['max_username_length'] . ' characters.';
						unset($username);
					}
					if(isset($username)) {
						$update_data['username'] = $username;
					}
					$pwerror = false;
					if($password != $password_verification) {
						$error[] = 'The new password does not match with the verification.';
						$pwerror = true;
					}
					if(strlen($password) > 0 && strlen($password) < $ion_auth_config['min_password_length'] || strlen($password) > $ion_auth_config['max_password_length'] && strlen($password) != 0) {
						$error[] = 'The password is is too long or too short. It must contain between ' . $ion_auth_config['min_password_length'] . ' and ' . $ion_auth_config['max_password_length'] . ' characters.';
						$pwerror = true;
					}
					if($pwerror != true) $update_data['password'] = $password;
					$update_data['first_name'] 	= $first_name;
					$update_data['last_name'] 	= $last_name;
					$update_data['about']		= $about;
					$this->ion_auth->update($user->id, $update_data);
					$data['form_validation'] = array('success' => TRUE);
					if(count($error) > 0) {
						$data['form_validation']['errors'] = TRUE;
						$data['form_validation']['messages'] = $error;
					} else {
						$data['form_validation']['messages'] = array('Successfully updated. Reload to see full changes.');
					}
				} else {
					$data['form_validation'] = array('success' => FALSE, 'errors' => TRUE, 'messages' => array('The old password is wrong.'));
				}
			} 


			$this->template->add_js('jquery.form');
			$this->template->add_js('ajax_upload.settings', $this);
			$userbackground = iimage($user->id, 'background');
			if($userbackground) $this->template->variable('eyecatcher_image', $userbackground, true);
			$this->_render_page('user/edit', $data);
		}
	}

	function _render_page($view, $data=null, $render=false)
	{

		$this->viewdata = (empty($data)) ? $this->data: $data;

		$view_html = $this->load->view($view, $this->viewdata, $render);

		if (!$render) return $view_html;
	}

	function _remap($method, $params)
	{
		if(method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		} 
		elseif((string) (int) $method == $method)
		{
			$params['id'] = $method;
			return call_user_func_array(array($this, 'view'), $params);
		}
		else
		{
			$params['string'] = $method;
			return call_user_func_array(array($this, 'search'), $params);
		}
	}
}