<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Blog_model extends CI_Model
{
	private $_table = 'blog';
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library('ion_auth');
		$this->_user = $this->ion_auth->user()->row();
	}

	public function get_by_id($id)
	{
		$query = $this->db->get_where($this->_table, array('id' => $id));
		return $query->row();
	}

	public function get_by_slug($slug)
	{
		$query = $this->db->get_where($this->_table, array('slug' => $slug));
		return $query->row();
	}

	public function get_posts_for_user($userid = false, $posts = 10, $start = 0, $is_admin = false)
	{
		if($posts < 0 || $posts === false || $posts != isint($posts)) $posts = 10;
		if($start < 0 || $start === false || $start != isint($start)) $start = 0;
		$this->db->order_by('id', 'desc');
		if($is_admin) $query = $this->db->get($this->_table, $posts, $start);
		else {
			$this->db->where('public', true);
			if($userid !== false) $this->db->or_where('user_id', $userid);
			$query = $this->db->get($this->_table, $posts, $start);
		}
		return $query->result_array();
	}

	public function max_pagination($posts = 10, $userid = false, $is_admin = false)
	{
		if($posts < 0 || $posts === false || $posts != isint($posts)) $posts = 10;
		$this->db->order_by('id', 'desc');
		if($is_admin) $query = $this->db->get($this->_table);
		else {
			$this->db->where('public', true);
			if($userid !== false) $this->db->or_where('user_id', $userid);
			$query = $this->db->get($this->_table);
		}
		return ceil($query->num_rows() / $posts);
	}

	public function insert($headline, $body, $enabled, $slug = FALSE)
	{
		if($slug == FALSE) $slug = $headline;
		$slug = $this->create_slug($slug);
		$data = array(
			'headline' 	=> $headline,
			'body'		=> $body,
			'user_id'	=> $this->_user->id,
			'timestamp'	=> date('Y-m-d H:i:s'),
			'slug'		=> $slug,
			'public'	=> false
		);
		if($enabled == true) $data['public'] = true;
		$this->db->insert($this->_table, $data);
		$this->db->order_by("id", "DESC");
		return $this->db->get($this->_table)->row()->slug;
	}

	public function create_slug($string)
	{
		$slug = strip_tags($string);
		$slug = strtolower(str_replace(' ', '-', $slug));
		$slug = preg_replace("/(&[a-z]*;)/", '', $slug); // Remove htmlentities
		$slug = preg_replace("/([^a-z0-9\-]*)/", '', $slug); // Only allow a-z, 0-9 and "-"
		$query = $this->db->get_where($this->_table, array('slug' => $slug));
		if($query->num_rows() > 0)
		{
			$query = $this->db->query(
'SELECT  `AUTO_INCREMENT` 
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA =  ' . $this->db->escape($this->db->database) . '
AND TABLE_NAME = ' . $this->db->escape($this->_table)
			);
			$slug .= '-' . $query->row()->AUTO_INCREMENT;	
		}
		return $slug;
	}
}