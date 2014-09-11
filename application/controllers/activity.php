<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Activity extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->library('ion_auth');
		$this->load->model('subgroup_model');
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
		$user = $this->ion_auth->user()->row();
		$data['user'] = $user;
		if($user) {
			$permissions = $this->subgroup_model;
			$permissions->set_user($user->id);
			$permissions->set_parent('activity_log');

			if($permissions->user_in_group()) { $data['may_submit'] = TRUE; $may_submit = TRUE; }
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

		$posts = $this->activity_log_model->get_newest_posts_with_users(!$may_submit, $start);
		$data['posts'] = $posts;
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