<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {
	public $viewdata;

	function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->helper('url');
		$this->load->helper('template');
		$this->load->helper('language');
		$this->load->helper('captcha');
		$this->load->library('fileinfo');
		$this->load->library('auth');
		$this->load->library('reCaptcha');
		$this->load->library('template');
	}

	function index()
	{
		$this->form_validation->set_rules('search', 'search field', 'xss_clean|required');
		if ($this->form_validation->run() == true) {
			$search = $this->input->post('search');
			redirect('/user/search/' . urlencode($search));
		}
		$user = $this->auth->user();
		if($user) {
			redirect('/user/' . $user->id);
		} else {
			redirect('/user/register');
		}
	}

	function login($site = false) {
		if($user = $this->auth->user()) redirect('user/' . $user->id);
		$data = $this->_get_login_form();
		$data['errors'] = false;
		if(isset($_POST['submit'])) {
			if($this->auth->login($_POST['username_email'], $_POST['password'], isset($_POST['stay_logged_in']))) {

			} else {
				$data['errors'] = array('Your password does not match to your e-mail or username.');
			}
		}

		$this->_render_page('user/login', $data);
	}

	function logout() {
		$this->auth->logout();
		$this->_render_page('user/logout');
	}

	function register()
	{
		$this->template->set_title("Create User");
		$this->data['errors'] = false;
		if(isset($_POST['submit'])) {
			$this->recaptcha->check_answer();
			$errors = $this->auth->register_user(
				$_POST['email'], 
				$_POST['username'], 
				$_POST['password'],
				$_POST['password_confirm'],
				$this->recaptcha->is_valid
				);
			if(count($errors) > 0 && is_array($errors)) {
				$this->data['errors'] = $errors;
			} else {
				$this->_render_page('user/register_success', $this->data);
				return;
			}
		}

		$this->data = array_merge($this->data, $this->_get_register_form());
		$this->_render_page('user/register', $this->data);
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

		$data['users'] = $this->auth->users_like($string);

		$this->_render_page('user/list', $data);
	}

	function view($id = NULL)
	{
		$user = $this->auth->user();
		if(!$id) redirect('/user/view/' . $user->id, 'refresh');
		if(!$user || $id != $user->id) $view = $this->auth->user($id); else $view = $user;
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

	function edit($slug = false, $value = false, $ajax = false) {
		$user = $this->auth->user();
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
			$user = $this->auth->user();
			$data['user'] = $user;
			$data['edit_form']['username'] = array(
				'type' => 'text',
				'value' => $user->username,
				'autocomplete' => 'off',
				'name' => 'username',
				'id' => 'edit_username'
			);
			$data['edit_form']['ingame_name'] = array(
				'type' => 'text',
				'value' => $user->ingame_name,
				'autocomplete' => 'off',
				'name' => 'ingame_name',
				'id' => 'edit_ingame_name'
			);
			$data['edit_form']['email'] = array(
				'type' => 'email',
				'value' => $user->email,
				'autocomplete' => 'off',
				'name' => 'email',
				'id' => 'edit_email'
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
				$this->config->load('auth');
				$error = array();
				$username 				= $this->input->post($data['edit_form']['username']['name']);
				$password				= $this->input->post($data['edit_form']['password']['name']);
				$password_verification 	= $this->input->post($data['edit_form']['password_verification']['name']);
				$about 					= $this->input->post($data['edit_form']['about']['name']);
				$ingame_name 			= $this->input->post($data['edit_form']['ingame_name']['name']);

				$data['edit_form']['username']['value'] 	= $username;
				$data['edit_form']['about']['value'] 		= $about;
				if($this->auth->check_password_id($user->id, $this->input->post($data['edit_form']['old_password']['name']))) {
					if($this->auth->user_exists($username) && $user->username != $username) {
						$error[] = 'The username already exists.';
						unset($username);
					}
					if(strlen($username) > $this->auth->max_username_length()) {
						$error[] = 'The username is too long. The username may contain ' . $this->auth->max_username_length() . ' characters.';
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
					if(strlen($password) == 0 && strlen($password_verification) == 0) {
						$pwerror = true;
					} elseif(strlen($password) < $this->config->item('password_min_length')) {
						$error[] = 'The password is is too short. It must contain at least ' . $this->config->item('password_min_length') . ' characters.';
						$pwerror = true;
					}
					if(strlen(str_replace(' ', '', $ingame_name)) === 0) $ingame_name = NULL;
					if(strlen($ingame_name) > $this->auth->max_ingame_name_length()) {
						$error[] = 'The ingame-name is too long. The ingame_name may contain ' . $this->auth->max_ingame_name_length() . ' characters.';
					} elseif($this->auth->ingame_name_exists($ingame_name) && $user->ingame_name != $ingame_name) {
						$error[] = 'The ingame-name already exists. Please choose another one.';
					} else {
						$update_data['ingame_name'] = $ingame_name;
					}
					if($pwerror != true) $update_data['password'] = $password;
					$update_data['about'] = $about;
					$this->auth->update_user($user->id, $update_data);
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

	private function _get_register_form() {
		$data['username'] = array(
			'name'  => 'username',
			'id'    => 'username',
			'type'  => 'text',
			'value' => isset($_POST['username']) ? $_POST['username'] : ''
		);
		$data['email'] = array(
			'name'  => 'email',
			'id'    => 'email',
			'type'  => 'text',
			'value' => isset($_POST['email']) ? $_POST['email'] : '',
		);
		$data['password'] = array(
			'name'  => 'password',
			'id'    => 'password',
			'type'  => 'password',
			'value' => isset($_POST['password']) ? $_POST['password'] : '',
		);
		$data['password_confirm'] = array(
			'name'  => 'password_confirm',
			'id'    => 'password_confirm',
			'type'  => 'password',
			'value' => isset($_POST['password_verification']) ? $_POST['password_verification'] : '',
		);
		$data['captcha'] = $this->template->prevent_variables($this->recaptcha->get_html());
		return $data;
	}

	private function _get_login_form() {
		$data['username_email'] = array(
			'name'  => 'username_email',
			'id'    => 'username_email',
			'type'  => 'text',
			'value' => isset($_POST['username_email']) ? $_POST['username_email'] : ''
		);
		$data['password'] = array(
			'name'  => 'password',
			'id'    => 'password',
			'type'  => 'password',
			'value' => isset($_POST['password']) ? $_POST['password'] : '',
		);
		$data['stay_logged_in'] = array(
			'name'  => 'stay_logged_in',
			'id'    => 'stay_logged_in',
			'type'  => 'checkbox',
		);
		if(isset($_POST['stay_logged_in'])) {
			$data['stay_logged_in']['checked'] = 'checked';
		}
		return $data;
	}

	function _render_page($view, $data=null, $render=false)
	{
		if(!empty($data)) $this->viewdata = $data;
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