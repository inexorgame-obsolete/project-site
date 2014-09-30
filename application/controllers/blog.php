<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Blog extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('Config_validations', array('data'));

		$this->load->model('blog_model');
		$this->load->database();
		$this->load->config('blog');
		$this->load->config('data', TRUE);
		$this->load->helper('blog_helper');
		$this->load->library('htmlfilter');
		$this->load->library('permissions');
		$this->load->library('auth');

		$this->load->library('template');
	}

	public function index($site = 1)
	{
		if($site != isint($site)) $site = 1;
		$site = $site - 1;
		$start = $site * 10;
		$creators = array();
		$user = $this->auth->user();

		if($user) {
			$this->permissions->set_user($user->id);
		}
		$permissions = $this->permissions->has_user_permission('blog');

		$posts = $this->blog_model->get_posts_for_user($user ? $user->id : false, 10, $start, $this->permissions->has_user_permission('blog_publish_all'));
		for($i = 0; $i < count($posts); $i++)
		{
			$posts[$i]['body'] = unserialize($posts[$i]['body']);
			if(!isset($creators[$posts[$i]['user_id']])) {
				$creators[$posts[$i]['user_id']] = $this->auth->user($posts[$i]['user_id']);
			}
		}
		$data = array(
			'posts' 			=> $posts, 
			'creators' 			=> $creators,
			'max_pagination'	=> $this->blog_model->max_pagination(10, $user ? $user->id : false, $this->permissions->has_user_permission('blog_publish_all')),
			'current_page'		=> $site + 1
		);

		$data['user_may_release'] = false;
		$data['user_edit_others'] = false;
		if($permissions && $this->permissions->has_user_permission('blog_publish_all')) $data['user_may_release'] = true;
		if($permissions && $this->permissions->has_user_permission('blog_edit_all')) $data['user_edit_others'] = true;
		$this->load->view('blog/blog', $data);
	}

	public function view($slug = false)
	{
		if(!$slug)
		{
			$this->index();
			return;
		}
		$user = $this->auth->user();
		if($user == false)
		{
			$permissions = false;
		} else {
			$this->permissions->set_user($user->id);
			$permissions = $this->permissions->has_user_permission('blog_create');
		}

		$access = false;
		if($slug == isint($slug)) {
			$entry = $this->blog_model->get_by_id($slug);
		} else {
			$entry = $this->blog_model->get_by_slug($slug);
		}
		if(!$entry)
		{
			$this->template->site_does_not_exist();
			return;
		}
		$entry->body = unserialize($entry->body);
		if((isset($user) && $permissions && ($user->id == $entry->user_id || $this->permissions->has_user_permission('blog_publish_all'))) || $entry->public == true) $access = true;
		else { $this->template->render_permission_error(); return; }
		$data['entry'] = $entry;
		$data['creator'] = $this->auth->user($entry->user_id);


		$data['user_may_release'] = false;
		$data['user_edit_others'] = false;
		if($permissions && $this->permissions->has_user_permission('blog_publish')) $data['user_may_release'] = true;
		if($permissions && $this->permissions->has_user_permission('blog_edit_all')) $data['user_edit_others'] = true;

		$this->load->view('blog/view', $data);
	}

	public function create($subblog = 'main') {

		$user = $this->auth->user();
		
		if(!$user) { redirect('user/register'); return; }

		$this->permissions->set_user($user->id);
		
		if(!$this->permissions->has_user_permission('blog_create')) { $this->template->render_permission_error(); return; }


		$allow_release = false;
		if($this->permissions->has_user_permission('blog_publish'))
		{
			$allow_release = true;
		}

		// Adding JS-WYSIWYG-editor and file-manager
		$this->template->add_head('<script src="' . data('plugins/tinymce/tinymce.min.js') . '"></script>');
		$this->template->add_head('<script src="' . data('plugins/tinymce/jquery.tinymce.min.js') . '"></script>');
		$this->template->add_js('inline_editor', 'blog');

		$this->template->print_console($this->input->post('headline'));
		$data = array('form' => $this->_get_create_form_data($allow_release));
		$this->template->add_js('jquery.form');
		$this->template->add_js('ajax_upload.settings', $this);
		
		// Processing

		if($this->input->post('submit') || $this->input->post('validate'))
		{

			$this->htmlfilter->markup = $this->input->post('headline');
			$this->htmlfilter->filter = $this->config->item('headlinefilter');
			$this->htmlfilter->parse();
			$result['headline'] = $this->htmlfilter->markup;

			$this->htmlfilter->replaces = $this->config->item('replaces');
			if($this->input->post('submit'))
			{
				$data_config = $this->config->item('data');

				if(!isset($this->htmlfilter->replaces['search']) || !isset($this->htmlfilter->replaces['replace']))
				{
					$this->htmlfilter->replaces['search'] = array();
					$this->htmlfilter->replaces['replace'] = array();
				}

				$this->htmlfilter->replaces['search'][]  = $data_config['location']['external'] . $data_config['dir']['user'];
				$this->htmlfilter->replaces['replace'][] = '{userdata}';

				$this->htmlfilter->replaces['search'][]  = $data_config['location']['external'] . $data_config['dir']['data'];
				$this->htmlfilter->replaces['replace'][] = '{data}';

				$this->htmlfilter->replaces['search'][]  = $data_config['location']['external'];
				$this->htmlfilter->replaces['replace'][] = '{base}';

				if(!isset($this->htmlfilter->attr_replaces['search']) || !isset($this->htmlfilter->attr_replaces['replace']))
				{
					$this->htmlfilter->attr_replaces['search'] = array();
					$this->htmlfilter->attr_replaces['replace'] = array();
				}

				$this->htmlfilter->attr_replaces['search'][]  = $data_config['location']['external'] . $data_config['dir']['user'];
				$this->htmlfilter->attr_replaces['replace'][] = '{userdata}';

				$this->htmlfilter->attr_replaces['search'][]  = $data_config['location']['external'] . $data_config['dir']['data'];
				$this->htmlfilter->attr_replaces['replace'][] = '{data}';

				$this->htmlfilter->attr_replaces['search'][]  = $data_config['location']['external'];
				$this->htmlfilter->attr_replaces['replace'][] = '{base}';

			}
			$this->htmlfilter->markup = $this->input->post('text');
			$this->htmlfilter->filter = $this->config->item('bodyfilter');
			$this->htmlfilter->parse();
			$result['body'] = $this->htmlfilter->markup;

			$data['result'] = array('body' => create_html_from_array($result['body'], 0), 'headline' => create_html_from_array($result['headline']), 0);
			$data['form']['text']['value'] = create_html_from_array($result['body'], 0);
			$data['form']['headline']['value'] = create_html_from_array($result['headline'], 0);
		} else {
			$data['result'] = false;
		}
		if($this->input->post('submit'))
		{
			$release = FALSE;
			if($allow_release && $this->input->post('enable')) $release = TRUE;
			$slug = create_html_from_array($result['headline']);
			if(str_replace(' ', '', strlen($this->input->post('slug'))) > 0) $slug = $this->input->post('slug');
			$redirect = $this->blog_model->insert(create_html_from_array($result['headline']), serialize($result['body']), $release, $slug);
			redirect('blog/view/' . $redirect);
		}





		$this->load->view('blog/create', $data);
	}

	private function _get_create_form_data($allow_enable = FALSE) 
	{
		$data['form'] = array('data-create' => 'form');
		$data['headline'] = array(
			'class' 		=> 'create-post',
			'type'			=> 'text',
			'value' 		=> $this->input->post('headline'),
			'name'			=> 'headline',
			'placeholder' 	=> 'Your headline',
			'data-create'	=> 'headline-form',
			'class' 		=> 'js-hide'
		);
		$data['text'] = array(
			'value'			=> $this->input->post('text'),
			'name'			=> 'text',
			'placeholder'	=> 'Your post content...',
			'data-create'	=> 'text-form',
			'class' 		=> 'js-hide'
		);
		$data['submit'] = array(
			'type'			=> 'submit',
			'value'			=> 'Submit post',
			'name'			=> 'submit',
			'data-create'	=> 'submit',
			'title'			=> 'Submit the post'
		);
		$data['validate'] = array(
			'type'			=> 'submit',
			'value'			=> 'Validate',
			'title'			=> 'Validate and check after the post ran through the Server-Validator. The post will be validated on submit again.',
			'name'			=> 'validate'
		);
		$data['slug'] = array(
			'type'			=> 'text',
			'value'			=> $this->input->post('slug'),
			'name'			=> 'slug',
			'id'			=> 'slug_name'
		);
		$data['slug_label'] = array(
			'content'		=> 'Slug',
			'for'			=> $data['slug']['id'],
			'title'			=> 'The slug is the text displayed in the URI, for better human and machine readability.'
		);
		if($allow_enable == TRUE)
		{
			$data['enable'] = array(
				'value'		=> 'enable',
				'checked'	=> $this->input->post('enable'),
				'id'		=> 'enable_public',
				'name'		=> 'enable'
			);
			$data['enable_label'] = array(
				'content'	=> 'Public ',
				'for'		=> $data['enable']['id']
			);
		}
		return $data;
	}

	public function _remap($method, $params)
	{
		if($method == isint($method))
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