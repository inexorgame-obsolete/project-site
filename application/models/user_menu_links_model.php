<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_menu_links_model extends CI_Model {

	// The table in the database
	private $_table = 'user_menu_links';

	/**
	 * Magic Method __construct();
	 */
	function __construct() {
		$this->load->database();
	}

	/**
	 * Get the menu links which have default = 1 or $add and are not $remove
	 * @param array $add Id's of links which should be added
	 * @param array $remove Id's of links which should not be shown
	 * @return object containing objects of links
	 */
	public function get_links($add = array(), $remove = array()) 
	{
		$where  = ' ((( `link` IS NOT NULL AND `default` = 1 )';
		if(count($add) != 0)
		{
			$where .= ' OR ( id';
			$where .= $this->_in($add);
			$where .= ')';
		}
		$where .= ')';
		if(count($remove) != 0)
		{
			$where .= ' AND ( id';
			$where .= $this->_not_in($remove);
			$where .= ')';
		}
		$where .= ')';
		$this->db->where($where, NULL, FALSE);
		$this->db->order_by('order', 'DESC');
		$results = $this->db->get($this->_table)->result();
		$parents = array();
		$return = array();
		$i = 0;
		foreach($results as $r)
		{
			if(!isset($parents[$r->parent_id]) && $r->parent_id != NULL)
			{
				$return[$i] = $this->get($r->parent_id);
				$return[$i]->childs = array();
				$parents[$r->parent_id] = $i;
				$i++;
			} 
			if($r->link != NULL && $r->parent_id == NULL) {
				if(!isset($parents[$r->id])) {
					$parents[$r->id] = $i;
					$return[$i] = $r;
					$return[$i]->childs = array();
					$i++;
				}
			} else {
				$return[$parents[$r->parent_id]]->childs[$r->id] = $r;
			}
		}
		return $return;
	}

	/**
	 * Gets a menu-link by its id
	 * @param int $id menu-link-id
	 * @return object menu-link-object
	 */
	public function get($id) {
		$this->db->where('id', $id);
		return $this->db->get($this->_table)->row();
	}

	/**
	 * Adds parents to a link; Is added like $object->parents[parent_id]
	 * @param object &$object the object containing the menu-link-objects
	 */
	public function add_parents(&$object)
	{
		// via IN - query parents nach order sortieren
		if(!isset($object->parents))
		{
			$object->parents = array();
		}
		foreach($object as $i => $o)
		{
			if(!isset($object->parents[$o->parent_id])) $object->parents[$o->parent_id] = $this->get($o->parent_id);
		}
	}

	/**
	 * Creates a NOT-IN-SQL string
	 * @param array $array values which should be in the NOT-IN string
	 * @return string NOT-IN string (not including the column)
	 */
	private function _not_in($array)
	{
		if(strlen($this->_in($array)) == 0) return '';
		return ' NOT' . $this->_in($array);
	}

	/**
	 * Creates a IN-SQL string
	 * @param array $array values which should be in the IN string
	 * @return string IN string (not including the column)
	 */
	private function _in($array)
	{
		$return = ' IN (';
		$count = 0;
		$str = '';
		foreach($array as $a) 
		{
			if(isint($a))
			{
				$str .= ', \'' . $a . '\'';
				$count++;
			}
		}
		if($count == 0) return '';
		$str = substr($str, 2);
		return $return . $str . ')';
	}

}

/* End of file user_menu_links_model.php */
/* Location: ./application/models/user_menu_links_model.php */