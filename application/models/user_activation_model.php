<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_activation_model extends CI_Model {
	private $_table = 'user_activation';
	public function __construct() {
		$this->load->database();
	}
	
	public function remove_expired()
	{
		$this->db->where('expiration <', date('Y-m-d H:i:s'));
		$this->db->delte($this->_table);
	}

	public function insert($userid, $activationcode, $date = false)
	{
		if(!is_string($date)) $date = date('Y-m-d H:i:s', time()+172800);
		$this->remove_users_codes($userid);
		$data = array(
			'user_id' => $userid,
			'code' => $activationcode,
			'expiration' => $date
		);
		$this->db->insert($this->_table, $data);
	}

	public function remove_users_codes($userid)
	{
		$this->db->where('user_id', $userid);
		$this->db->delete($this->_table);
	}

	public function delete($id) 
	{
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

	public function is_valid($userid, $activationcode, $delete_on_valid = false)
	{
		$this->db->where('user_id', $userid);
		$this->db->where('code', $activationcode);
		$query = $this->db->get($this->_table);
		$data = $query->row();
		if(isset($data->user_id))
		{
			if(strtotime($data->expiration) < time())
			{
				$this->delete($data->id);
				return false;
			}
			if($delete_on_valid) $this->delete($data->id);
			return true;
		}
		return false;
	}
}
