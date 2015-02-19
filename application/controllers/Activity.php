<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Activity extends CI_Controller {

	/**
	 * Magic Method __construct()
	 * Adding library
	 */
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

	/**
	 * Index and single page
	 * @param int $site pagination-link
	 */
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
		$data['edit_all'] = $this->permissions->has_user_permission('activity_log_edit_all');
		$data['max_pagination'] = $this->activity_log_model->max_pagination(30, !$may_submit);
		$this->load->view('activity/index', $data);
	}

	/**
	 * Edit posts
	 * @param int $postid activity-log-postid
	 */
	public function edit($postid, $pagination_site = false)
	{
		$user = $this->auth->user();
		$this->permissions->set_user($user->id);

		// Check all needed permissions for this user
		if(!$this->permissions->has_user_permission('activity_log')) { redirect('activity'); return; }
		if(!isint($postid)) { $this->template->render_error('Post does not exists', 'A post with this ID does not exists.'); return; }
		$post = $this->activity_log_model->get_post($postid);
		if(!isset($post->id)) { $this->template->render_error('Post does not exists', 'A post with this ID does not exists.'); return; }
		if($post->user_id != $user->id && !$this->permissions->has_user_permission('activity_log_edit_all')) { $this->template->render_permission_error(); return; }
		
		// User has the needed permissions
		$data = array();
		$data['user'] = $user;
		$data['post'] = $post;
		$data['form'] = $this->_edit_post_data($post);
		$redirect = (isint($pagination_site)) ? 'activity/' . $pagination_site : 'activity';
		$data['creator'] = ($post->user_id != $user->id) ? $this->auth->user($post->user_id) : $user;

		if(isset($_POST['delete']))
		{
			$this->activity_log_model->remove($postid);
			redirect($redirect);
		}
		if(isset($_POST['submit']))
		{
			if(str_replace(' ', '', $_POST['text']) != '') {
				$udata = array(
					'changes' => $_POST['text'],
					'public' => false
				);
				if(isset($_POST['is_public']))
				{
					$udata['public'] = true;
				}
				$this->activity_log_model->update($postid, $udata);
				redirect($redirect);
			}
		}

		$this->load->view('activity/edit', $data);
	}

	/**
	 * Form-data for the index-site
	 * @return array form-data
	 */
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

	private function _edit_post_data($post)
	{
		$data['text'] = array(
			'name' => 'text',
			'placeholder' => 'Your activity...',
			'value' => $post->changes
		);
		$data['public'] = array(
			'name' => 'is_public',
			'id' => 'form_activity_is_public',
			'value' => 'make_public',
			'title' => 'Checked: Show activity to everyone. Unchecked: Show acitivity to developers only.'
		);
		if($post->public == true)
		{
			$data['public']['checked'] = 'checked';
		}
		$data['public_label'] = array(
			'value' => 'Public activity',
			'attributes' => array('title' => $data['public']['title']),
			'for' => $data['public']['id']
		);
		$data['submit'] = array(
			'name' => 'submit',
			'value' => 'Post changes'
		);
		$data['delete'] = array(
			'name' => 'delete',
			'value' => 'Delete this post',
			'class' => 'left'
			);
		return $data;
	}

	/**
	 * Remapper, $parameters are not used, $method set navigation
	 * @param int $method pagination-site
	 * @param array $params 
	 */
	public function _remap($method, $params)
	{
		if(isint($method))
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
