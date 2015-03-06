<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Ratingapi extends CI_Controller {

	/**
	 * Magic Method __construct()
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->load->library('rating');
		$this->load->library('apiaccess');
		$this->apiaccess->check(true);
	}

	/**
	 * The actual site. Will always execute
	 * @param string $module     the module-name to load. if not set, ratings-submit will be checked
	 * @param string $identifier the identifier-name
	 */
	public function index($module = false, $identifiers = false)
	{
		if($module == false)
			$output = $this->rating->check();
		elseif(empty($identifiers))
			$output = "No identifier set. Module: " . $module;
		else
			$output = $this->rating->get_ratings($module, count($identifiers) == 1 ? $identifiers[0] : $identifiers, true);

		$this->set_output($output);
	}

	private function set_output($output)
	{
		return $this->output->set_content_type('application/json')->set_output(json_encode($output));
	}

	public function _remap($method, $parameters = array())
	{
		if($method != 'index')
		{
			$parameters = array($parameters);
			array_unshift($parameters, $method);
		}
		call_user_func_array(array($this, 'index'), $parameters);
	}
}
?>
