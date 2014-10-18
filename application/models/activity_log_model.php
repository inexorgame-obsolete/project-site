<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Activity_log_model extends CI_Model
{

	// The table in the database
	private $_table = 'activity_log';

	// The user object
	private $_user = false;

	/**
	 * Magic Method __construct();
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library('auth');
		$this->_user = $this->auth->user();
	}

	/**
	 * Gets the newest activity log posts
	 * @param bool $public_only Only posts which are viewable for all users
	 * @param int $start Offset
	 * @param int $posts Limit
	 * @return array The posts
	 */
	public function get_newest_posts($public_only = TRUE, $start = 0, $posts = 30)
	{
		$this->db->order_by('id', 'DESC');
		if($public_only)
		{
			$query = $this->db->get_where($this->_table, array('public' => TRUE), $start, $posts);
		} else {
			$query = $this->db->get($this->_table, $start, $posts);
		}
		return $query->result_array();
	}

	/**
	 * Gets newest posts and automatically adds the user-objects.
	 * @param bool $public_only Only posts which are viewable for all users
	 * @param int $start Offset
	 * @param int $posts Limit
	 * @return array The posts
	 */
	public function get_newest_posts_with_users($public_only = TRUE, $start = 0, $posts = 30) 
	{
		$posts = $this->get_newest_posts($public_only, $posts, $start);
		$users = array();
		foreach($posts as $i => $p)
		{
			if(isset($users[$p['user_id']])) $posts[$i]['user'] = $users[$p['user_id']];
			else $posts[$i]['user'] = $this->auth->user($p['user_id']);
		}
		return $posts;
	}

	/**
	 * Creates new activity-log-entry
	 * @param string $text The content
	 * @param bool $public Only posts which are viewable for all users
	 */
	public function update_activity($text, $public)
	{
		if($public) $public = TRUE;
		else $public = FALSE;
		$this->db->insert($this->_table, 
			array(
				'changes'   => $text, 
				'public'    => $public, 
				'user_id'   => $this->_user->id, 
				'timestamp' => date('Y-m-d H:i:s')
			)
		);
	}

	/**
	 * Updates an entry
	 * @param int $id activity-log-post-id
	 * @param array $data The new data
	 */
	public function update($id, $data)
	{
		$this->db->where('id', $id);
		$this->db->update($this->_table, $data);
	}

	public function remove($id)
	{
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

	/**
	 * Returns a single post
	 * @param int $id activity-log-post-id
	 * @return object activity-log-object
	 */
	public function get_post($id) 
	{
		return $this->db->get_where($this->_table, array('id' => $id))->row();
	}

	/**
	 * Returns how high the maximum pagination according to the posts per site is
	 * @param int $posts posts per site
	 * @param bool $public_only Only posts which are viewable for all users
	 * @return int max-pagination
	 */
	public function max_pagination($posts = 30, $public_only = TRUE)
	{
		if($posts < 0 || $posts === false || $posts != (string) (int) $posts) $posts = 30;
		if($public_only)
		{
			$query = $this->db->get_where($this->_table, array('public' => TRUE));
		} else {
			$query = $this->db->get($this->_table);
		}
		return ceil($query->num_rows() / $posts);
	}


}