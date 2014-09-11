<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shoutbox_model extends CI_Model
{
	private $_tables;
	public $sort = 'desc';
	public $limit = '30';
	function __construct () 
	{
		parent::__construct();
		$this->load->database();
		$this->load->library('ion_auth');
		$this->load->config('shoutbox', FALSE);
		$this->_tables = $this->config->item('tables');
	}

	public function get_shoutbox_info($id)
	{
		$query = $this->db->get_where($this->_tables['shoutbox'], array('id' => $id));
		return $query->result()[0];
	}

	public function get_shouts($id, $site = 0)
	{
		$this->db->order_by('time', $this->sort);
		$query = $this->db->get_where($this->_tables['shouts'], array('shoutbox_id' => $id), $this->limit, $site * $this->limit);
		return $query->result();
	}

	public function get_authors($shoutbox_id)
	{
		$this->db->select('user_id');
		$this->db->distinct();
		$query = $this->db->get_where($this->_tables['shouts'], array('shoutbox_id' => $shoutbox_id));
		$results = $query->result();
		return array_map(function ($o) { return $o->user_id; }, $results);
	}

	public function submit($shoutbox_id, $shout, $user_id)
	{
		$data = array(
			'shoutbox_id'	=> $shoutbox_id,
			'shout'			=> $shout,
			'user_id'		=> $user_id,
			'time'			=> date('Y-m-d H:i:s')
		);
		$this->db->insert($this->_tables['shouts'], $data);
		return;
	}

	public function user_shoutbox_permissions($shoutbox_id, $user_id = false)
	{
		$result = $this->db->get_where($this->_tables['shoutbox'], array(
			'id' => $shoutbox_id
		))->row();
		if($result->viewable == 'by_all') $default = 'view';
		else $default = false;
		if($user_id == false) {
			$user_id = $this->ion_auth->user()->row();
			if($user_id != false) $user_id = $user_id->id;
		}
		if((int) $user_id != $user_id || $user_id == false)
		{
			return $default;
		}
		if($result->id == $user_id) return 'write';

		$query = $this->db->get_where($this->_tables['users'], array(
			'user_id' => $user_id,
			'shoutbox_id' => $shoutbox_id
		));

		if($query->num_rows() == 1) 
		{
			$result = $query->row();
			if($result->view_only == true)
			{
				return 'view';
			}
			return 'write';
		}
		$groups = array_map(function ($a) { return $a->id; }, $this->ion_auth->get_users_groups($user_id)->result());

		$this->db->where_in('group_id', $groups);
		$this->db->order_by('view_only', 'DESC');
		$query = $this->db->get_where($this->_tables['users'], array(
			'shoutbox_id' => $shoutbox_id
		));
		if($query->num_rows() == 0)
		{
			return $default;
		}
		if($result->viewable == FALSE) {
			return 'write';
		}
		return 'view';
	}
}