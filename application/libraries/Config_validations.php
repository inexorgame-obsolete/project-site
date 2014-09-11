<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Config_validations {
	private $_CI;
	private $_validate_all_blacklist = array('__construct', 'validate_all');	// Functions which should not be executed on validate_all.

	public function __construct($validate = false) 
	{
		$this->_CI =& get_instance();

		if(is_array($validate))
		{
			foreach($validate as $v)
			{
				if(method_exists($this, 'validate_' . $v))
				{
					call_user_func_array(array($this, 'validate_' . $v), array());
				}
			}
		}
	}

	public function validate_all()
	{
		$methods = get_class_methods($this);
		foreach ($methods as $m) {
			if(!in_array($m, $this->_validate_all_blacklist))
			{
				call_user_func_array(array($this, $m), array());
			}
		}
	} 

	public function validate_data()
	{
		$this->_CI->load->config('data', TRUE);
		$config = $this->_CI->config->item('data');


		if($config['location']['internal'] === FALSE) $config['location']['internal'] = FCPATH;
		if($config['location']['external'] === FALSE) $config['location']['external'] = base_url();

		$this->_CI->config->set_item('data', $config);
	}
}