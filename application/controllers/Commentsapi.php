<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Commentsapi extends CI_Controller {
	
	private $_validated = false;

	/**
	 * Magic Method __construct();
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->library("auth");
		$this->load->library('comments');
	}

	public function index()
	{
		show_404();
	}

	public function api()
	{
		if(!isint($_POST['reference']))
			return $this->set_output(array("error" => "No valid reference provided."));

		if(!$_POST['get_answers'])
		{
			$reference = $this->comments->get($_POST['reference']);

			if($reference->answer_to == NULL)
			{
				$this->comments->set_module($reference->module);
				$this->comments->set_identifier($reference->identifier);
			}
		}

		if($this->_post() == false)
			return $this->set_output(array("error" => "Wrong post-information provided."));

		if(!$_POST['get_answers'])
		{
			if($_POST['action'] == 'next')
				$return = $this->comments->get_comments_after($_POST['reference'], 'ASC');
			else
				$return = $this->comments->get_comments_before($_POST['reference']);

			$this->comments->additional_info($return);

			if($_POST['action'] == 'next')
				$return['comments_left'] = $this->comments->count_comments_after($_POST['reference'])-count($return);
			else
				$return['comments_left'] = $this->comments->count_comments_before($_POST['reference'])-count($return);
		}
		else
		{
			$return = $this->comments->get_comments(array('answer-to' => $_POST['reference'], 'limit' => array(3), 'order' => 'ASC'));
			$this->comments->additional_info($return);
			$return = array_values($return);
			$return['comments_left'] = $this->comments->count_comments_after(end($return)->id);
			reset($return);
		}

		$return['get_answers'] = $_POST['get_answers'];
		$return['post'] = $_POST;
		$this->set_output($return);
	}

	public function create()
	{
		$_POST['comments-submit'] = true;
		$user = $this->auth->user();
		if(!isset($user->id)) 
		{
			$this->set_output(array("error" => "You have to log in or create an account to comment."));
			return;
		}
		if(!isset($_POST['comment']) || strlen($_POST['comment']) == 0)
		{
			$this->set_output(array("error" => "You have to enter a comment.", "comment" => $_POST['comment']));
			return;
		}
		if(isset($_POST['comment-answer-to']))
		{
			if(!isint($_POST['comment-answer-to']))
			{
				$this->set_output(array("error" => "No correct answer-id has been provided."));
				return;
			}
		}
		$submit = $this->comments->submit_comment();
		if($submit === false) 
		{
			$this->set_output(array("error" => "No correct module or identifier provided."));
			return;
		}
		$return = $this->comments->additional_info($this->comments->get($submit));
		$return->success = true;
		$this->set_output($return);
		return;
	}

	private function _post()
	{
		if(!$this->_validated)
		{
			if(!isset($_POST['action'])) return false;
			$_POST['action'] = strtolower($_POST['action']);
			if($_POST['action'] != 'prev' && $_POST['action'] != 'previous' && $_POST['action'] != 'next') return false;
			if(!isset($_POST['reference']) || !isint($_POST['reference'])) return false;
			$this->_validated = true;
			$_POST['get_answers'] = (isset($_POST['get_answers']) && $_POST['get_answers'] == "true");
		}
		return $_POST;
	}

	public function _remap($method, $params = array())
	{
		
		if($method == 'create')
		{
			$this->create();
			return;
		}
		$this->api($method, array_shift($params), $params);
	}

	private function set_output($output)
	{
		return $this->output->set_content_type('application/json')->set_output(json_encode($output));
	}
}

/* End of file irclog.php */
/* Location: ./application/controllers/irclog.php */
