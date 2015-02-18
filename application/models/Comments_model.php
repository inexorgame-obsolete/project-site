<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Comments_model extends CI_Model
{
	// The table in the database
	private $_table = 'comments';

	/**
	 * Magic Method __construct();
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Gets a comment by id.
	 * @param  int $id id of the comment
	 * @return object     object of the comment
	 */
	public function get_by_id($id)
	{
		$query = $this->db->get_where($this->_table, array('id' => $id));
		return $query->row();
	}

	/**
	 * Checks how many direcot answers to a comment exist.
	 * @param  int $id id of the comment
	 * @return int     number of direct comments
	 */
	public function answers_to($id)
	{
		return $this->db->where('id', $id)->num_rows();
	}

	/**
	 * Gets all comments and answers.
	 * for each depth limit needs one value. its the limit for answers to be loaded.
	 *
	 * @param  string $module module of the comment section
	 * @param  string $identifier identifier of the comment section
	 * @param  string $order order of the loading date
	 * @param  int $limit limit for comments
	 * @param  int $offset offset to the limit
	 * @return object comments
	 */
	public function get_comments($module, $identifier, $order = "DESC", $limit = 30, $offset = 0)
	{
		$this->db->order_by('date', $order);
		$this->db->limit($limit, $offset);
		$query = $this->db->get_where(array(
			"module"     => $module,
			"identifier" => $identifier),
		$this->_table);

		return $this->db->result();
	}

	/**
	 * Gets answers to a comment
	 * @param  int     $id     the comment id
	 * @param  string  $order  order of the loading (by date)
	 * @param  integer $limit  limit for comments
	 * @param  integer $offset offset to the limit
	 * @return object          answers
	 */
	public function get_answers($id, $order = "DESC", $limit = 10, $offset = 0)
	{
		$this->db->order_by('date', $order);
		$this->db->limit($limit, $offset);
		return $this->db->get_where(array("id" => $id), $this->_table)->result();
	}

	/**
	 * Creates a new comment to a comment section
	 * @param  string $module     the comment module
	 * @param  string $identifier the identifier for the module-section
	 * @param  int    $userid     id of the user who submits the comment
	 * @param  string $comment    the comment itself
	 * @return void
	 */
	public function comment($module, $identifier, $userid, $comment)
	{
		$this->db->insert($this->_table, array(
			'module'     => $module,
			'identifier' => $identifier,
			'user_id'    => $userid,
			'comment'    => $comment,
			'date'       => date('Y-m-d H:i:s')
		));
	}

	/**
	 * Answer to a comment
	 * Inserts row in the db.
	 *
	 * @param int $id id of the comment to answer to
	 * @param int $userid id of the user who submits the comment
	 * @param string $comment the comment content
	 */
	public function answer($id, $userid, $comment)
	{
		$this->db->insert($this->_table, array(
			'answer_to' => $id,
			'user_id'   => $userid,
			'comment'   => $comment,
			'date'      => date('Y-m-d H:i:s')
		));
	}
}