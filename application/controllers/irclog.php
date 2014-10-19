<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Irclog extends CI_Controller {

	// The IRC-Channel which is associated to this log.
	private $_channel = '#sauerfork';

	/**
	 * Magic Method __construct();
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->model('irc_model');
		$this->load->library('template');
		$this->template->add_css($this);
	}

	/**
	 * Index page
	 * @param int $startts Current pagination
	 * @param int $endts Logs per page
	 */
	public function index($startts = false, $endts = false)
	{
		if(!isint($startts)) $startts = 1;
		if(!isint($endts))   $endts   = 100;
		if($endts > 250) $endts = 250;
		$data['start'] = $startts;
		$data['results'] = $endts;
		$startts = ($startts - 1) * $endts;
		$data['log'] = $this->irc_model->get($startts, $endts);
		if(count($data['log']) == 0)
		{
			redirect('irclog');
			return;
		}
		$data['start_users'] = json_decode($this->irc_model->get_users($data['log'][count($data['log'])-1]->timestamp)->connected_users);
		$data['max_pagination'] = $this->irc_model->max_pagination($endts);
		$data['channel'] = $this->_channel;
		$this->load->view('irclog/index', $data);
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
			array_unshift($params, $method);
			return call_user_func_array(array($this, 'index'), $params);
		}
		elseif (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}
}

/* End of file irclog.php */
/* Location: ./application/controllers/irclog.php */