<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Cronjobs extends CI_Controller {

	// Dir of the cronjob-libraries
	private $_cronjobdir = 'application/libraries/cronjobs/';

	/**
	 * Magic Method __construct
	 */
	public function __construct() {
		parent::__construct();
		// Only allow Command-Line-Interface-requests, no external requests
		if(!is_cli()) 
		{
			show_404();
			die(); // for safety reasons if show_404() won't work as expected or properly.
		}
	}

	/**
	 * Index site
	 * @param string $group dir of a group-files
	 * @param array $methods Array of methods to be executed in group-files (parameters are not possible here)
	 * @return bool returns true if at least one file was loaded
	 */
	public function index($group, $methods)
	{
		if(!is_dir(FCPATH . $this->_cronjobdir . $group . '/')) return false;
		$cronjobs = scandir(FCPATH . $this->_cronjobdir . $group . '/');
		foreach($cronjobs as $cronjob)
		{
			if(substr($cronjob, strlen($cronjob) - 4) == '.php')
			{
				$loadlib = explode('/', $this->_load_if_exists($group . '/' . $cronjob));
				$loadlib = $loadlib[count($loadlib)-1];
				if(is_callable(array($this->$loadlib, 'on_group'))) {
					call_user_func_array(array($this->$loadlib, 'on_group_call'), array());
				}
				foreach($methods as $method) {
					if(is_callable(array($this->$loadlib, $method))) {
						call_user_func_array(array($this->$loadlib, $method), array());
					}
				}
			}
		}
		return true;
	}

	/**
	 * Load file-cronjob to execute multiple methods without parameters inside
	 * @param string $file file-location without .php
	 * @param array $methods array of methods to be executed
	 * @return bool returns true if file exists
	 */
	public function file($file, $methods)
	{
		$loadlib = $this->_load_if_exists($file . '.php');
		if(!$loadlib) return false;

		if(count($methods) == 0) {
			$methods[0] = 'on_function_call';
		}
		foreach($methods as $k => $method) {
			if(is_callable(array($this->$loadlib, $method))) {
				call_user_func_array(array($this->$loadlib, $method), array());
			}
		}
		return true;
	}

	/**
	 * Calls one method in one file with multiple parameters
	 * @param string $file file-location without .php
	 * @param string $method method-name
	 * @param array $params array of arguments; First index = first argument, second index = second argument...
	 * @return mixed returns false if file does not exists; else returns function-call-return
	 */
	public function filemethod($file, $method, $params) 
	{

		$loadlib = $this->_load_if_exists($file . '.php');
		if(!$loadlib) return false;

		if(is_callable(array($this->$loadlib, $method))) {
			return call_user_func_array(array($this->$loadlib, $method), $params);
		}	
	}

	/**
	 * Remaps the functions so default method for is index and calles by default default_group inside the file-groups
	 * @param string $method name of the method to call
	 * @param array $params parameters to be submitted
	 */
	public function _remap($method, $params = array())
	{
		if($method == 'file')
		{
			$file = $params[0];
			unset($params[0]);
			return call_user_func_array(array($this, 'file'), array($file, $params));
		} elseif($method == 'filemethod') {
			$file = $params[0];
			unset($params[0]);
			$method = $params[1];
			unset($params[1]);
			return call_user_func_array(array($this, 'filemethod'), array($file, $method, $params));
		} else {
			if($method == 'index') $method = 'default_group';
			return call_user_func_array(array($this, 'index'), array($method, $params));
		}
	}

	/**
	 * Loads cronjob-library if exists
	 * @param string $file file-location inside cronjob-dir
	 * @return type
	 */
	private function _load_if_exists($file) {
		if(!file_exists(FCPATH . $this->_cronjobdir . $file)) return false;
		$loadlib = substr($file, 0, strlen($file) - 4);
		$this->load->library('cronjobs/' . $loadlib);
		return $loadlib;
	}
}
