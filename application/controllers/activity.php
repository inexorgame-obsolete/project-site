<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Activity extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->library('auth');
		$this->load->library('permissions');
		$this->load->model('blog_model');
		$this->load->database();
		$this->load->helper('template_helper');
		$this->load->model('activity_log_model');

		$this->load->library('template');
	}

	public function index($site = 1) 
	{
		$data = array();
		$site = ($site > 0) ? $site : 1;
		$data['current_page'] = $site;
		$site = $site - 1;


		$start = $site * 30;

		$data['may_submit'] = FALSE;
		$may_submit = FALSE;
		$user = $this->auth->user();
		$data['user'] = $user;
		$see_all_posts = false;
		if($user) {
			$this->permissions->set_user($user->id);
			$see_all_posts = $this->permissions->has_user_permission('activity_log');

			if($this->permissions->has_user_permission('activity_log_create')) { $data['may_submit'] = TRUE; $may_submit = TRUE; }
		}
		$data['form'] = $this->_index_post_data();

		if($may_submit)
		{
			if($this->input->post('submit')) {
				$text = $this->input->post('text');
				if(str_replace(' ', '', $text) != '')
				{
					$make_public = false;
					if($this->input->post('is_public'))	$make_public = true;
					$this->activity_log_model->update_activity($this->input->post('text'), $this->input->post('is_public'));
				}
			}
		}

		$data['posts'] = $this->activity_log_model->get_newest_posts_with_users(!$see_all_posts, $start);	// 1st var is $public_only -> so opposite of $see_all_posts
		$data['max_pagination'] = $this->activity_log_model->max_pagination(30, !$may_submit);
		$this->load->view('activity/index', $data);
	}

	private function _index_post_data()
	{
		$data['text'] = array(
			'name' => 'text',
			'placeholder' => 'Your activity...'
		);
		$data['public'] = array(
			'name' => 'is_public',
			'id' => 'form_activity_is_public',
			'checked' => 'checked',
			'value' => 'make_public',
			'title' => 'Checked: Show activity to everyone. Unchecked: Show acitivity to developers only.'
		);
		$data['public_label'] = array(
			'value' => 'Public activity',
			'attributes' => array('title' => $data['public']['title']),
			'for' => $data['public']['id']
		);
		$data['submit'] = array(
			'name' => 'submit',
			'value' => 'Post changes'
		);

		return $data;
	}

	public function _remap($method, $params)
	{
		if($method == (string) (int) $method)
		{
			return $this->index($method);
		}
		elseif (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}

}
?>