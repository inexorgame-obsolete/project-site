<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Activity_log_model extends CI_Model
{
	private $_user = false;

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library('ion_auth');
		$this->_user = $this->ion_auth->user()->row();
	}

	public function get_newest_posts($public_only = TRUE, $start = 0, $posts = 30)
	{
		$this->db->order_by('id', 'DESC');
		if($public_only)
		{
			$query = $this->db->get_where('activity_log', array('public' => TRUE), $start, $posts);
		} else {
			$query = $this->db->get('activity_log', $start, $posts);
		}
		return $query->result_array();
	}

	public function get_newest_posts_with_users($public_only = TRUE, $start = 0, $posts = 30) 
	{
		$posts = $this->get_newest_posts($public_only, $posts, $start);
		$users = array();
		foreach($posts as $i => $p)
		{
			if(isset($users[$p['user_id']])) $posts[$i]['user'] = $users[$p['user_id']];
			else $posts[$i]['user'] = $this->ion_auth->user($p['user_id'])->row();
		}
		return $posts;
	}

	public function update_activity($text, $public)
	{
		if($public) $public = TRUE;
		else $public = FALSE;
		$this->db->insert('activity_log', 
			array(
				'changes'   => $text, 
				'public'    => $public, 
				'user_id'   => $this->_user->id, 
				'timestamp' => date('Y-m-d H:i:s')
			)
		);
	}

	public function max_pagination($posts = 30, $public_only = TRUE)
	{
		if($posts < 0 || $posts === false || $posts != (string) (int) $posts) $posts = 30;
		if($public_only)
		{
			$query = $this->db->get_where('activity_log', array('public' => TRUE));
		} else {
			$query = $this->db->get('activity_log');
		}
		return ceil($query->num_rows() / $posts);
	}


}