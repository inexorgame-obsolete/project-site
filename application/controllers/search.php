<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends CI_Controller {

	/**
	 * Magic Method __construct();
	 */
	function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->library('auth');
		$this->load->helper('url');
		$this->load->config('search', FALSE);
		$this->load->helper('template');
	}

	/**
	 * The index page controller
	 */
	function index() 
	{
		$target_error = false;
		$data = array();
		$searchtarget = $this->config->item('search');
		$this->form_validation->set_rules('search', 'Search', 'required');
		if(count($searchtarget) == 1) {
			$searchtarget = $searchtarget[key($searchtarget)][0];
		} elseif(!isset($searchtarget[$this->input->post('target')])) {
			$target_error = true;
		} else {
			$searchtarget = $this->input->post('target');
		}
		if($this->form_validation->run() == FALSE || $target_error)
		{
			$error = array();
			$validation_error = validation_errors();
			if($validation_error) {
				$error[] = $validation_error;
			}
			if($target_error) {
				$error[] = 'Please select a search-target.';
			}
			$data['validation_message'] = $error;
		} else {
			redirect($searchtarget . '/' . urlencode($this->input->post('search')));
		}

		$this->_render_page('search/search', $data);
	}

	/**
	 * Search API, json output
	 * @param string $object the category the search belongs to
	 * @param string $search the search string
	 * @param int $start search offset
	 * @param limit $limit results per page
	 */
	function api($object, $search, $start = 0, $limit = 30)
	{
		if(strlen($search) < $this->config->item('min_chars'))
		{
			$results = array('error' => array('message' => 'You have to enter minimum ' . $this->config->item('min_chars') . ' characters to get any results!', 'reason' => 'min_letters'));
		}
		else
		{
			if($start != (string) (int) $start) $start = 0;
			if($limit != (string) (int) $limit) $limit = 30;
			if(1 > $limit || $limit > $this->config->item('max_results')) $limit = 30;
			$search = urldecode($search);
			if($object == 'user')
			{
				$results = $this->_search_user($search, $start, $limit);
			}
			$results['results'] = count($results);
		}
		$this->output->set_content_type('application/json')->set_output(json_encode($results));
	}

	/**
	 * Searches a user
	 * @param string $username the username search string
	 * @param int $start offset
	 * @param int $limit results per page
	 * @return array user-info
	 */
	function _search_user($username, $start = 0, $limit = 30)
	{
		$whitelist_info = array('id', 'username', 'ingame_name', 'about', 'country_code'); // info submitted accessible via json api
		$users = $this->auth->users_like(array(
			'name'   => $username,
			'limit'  => $limit,
			'offset' => $start,
		));
		$return = array();
		foreach($users as $u) 
		{
			foreach($whitelist_info as $i) {
				if(isset($u->$i)) $return[$i] = $u->$i;
			}
		}
		return $return;
	}

	/**
	 * Renders the page
	 * @param string $view the view to render
	 * @param array $data the data to pass to the view
	 * @param bool $render FALSE: Direct output 
	 * @return mixed NULL when $render true; string when $render false
	 */
	function _render_page($view, $data = NULL, $render = FALSE)
	{

		$view_html = $this->load->view($view, $data, $render);

		if (!$render) return $view_html;
	}
}