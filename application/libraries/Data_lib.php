<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Data_lib {

	public $owner;	// Owner of a dir or file
	public $user;	// Current user trying to access the file
	private $_dir = array(
		'internal' => false,
		'external' => false,

		'base' => false,
		'filecontroller' => false,
		'relative' => false
	);				// Dir info - base: link to file; filecontroller: absolute path (with the filecontroller); relative: same as fc, but without FCPATH
	private $_CI;
	private $_dirinfo;	// Info of a dir with all the subdirs (contains files, filesizes, subdirs)
	private $_config;	// $this->_CI->load->config('data', TRUE); $this->config->item('data');
	private $_file;		// Path and info of a file
	private $_fs_extension;	// Extension matching to the file.
	private $_ownerdir = NULL;
	private $_final_name;
	public function __construct() 
	{
		$this->_CI =& get_instance();
		$this->_CI->load->library('auth');
		$this->_CI->load->library('fileinfo');
		$this->_CI->config->load('data', true);
		$this->_config = $this->_CI->config->item('data');
		foreach($this->_config['dir'] as $i => $v) {
			$this->_parse_dir($this->_config['dir'][$i]);
		}
	}

	public function set_file($file)
	{
		if(is_array($file) && isset($file['name']))
		{
			$this->_file = $file;
			$this->_file['path'] = $file['tmp_name'];
		} elseif(is_string($file)) {
			$this->_file['path'] = $file;
		} else {
			return false;
		}
		$this->_CI->fileinfo->filepath = $this->_file['path'];
		$this->_CI->fileinfo->init();
	}

	public function is_file_type($types)
	{
		if(is_string($types) && isset($this->_config['filetypes'][$types]))
		{
			$types = $this->_config['filetypes'][$types];
		}
		if($result = $this->_CI->fileinfo->is_file_type($types)) {
			$this->_fs_extension = $result[0];
		}
		return $result;
	}

	public function ownerdir($set = false) {
		if($this->_ownerdir == NULL || $set) {
			if($this->owner) {
				$this->_ownerdir = $this->_config['dir']['user'];
				$this->_parse_dir($this->_ownerdir, false);
				$this->_ownerdir = FCPATH . $this->_ownerdir . $this->owner->unique_id . '/';
			} else {
				$this->_ownerdir = false;
			}
		}
		return $this->_ownerdir;
	}

	public function move_file($dir = false, $name = false, $use_fs_extension = true)
	{
		if($dir == false) {
			$dir = $this->ownerdir();
		} else {
			$this->_parse_dir($dir, false);
		}
		if(is_dir($dir))
		{
			if($name != false) $name = pathinfo($name);
			elseif(isset($this->_file['name'])) $name = pathinfo($this->_file['name']);
			else $name = pathinfo($this->_file['path']);
			if($use_fs_extension == true) $name['extension'] = $this->_fs_extension;
			$name = $name['filename'] . '.' . $name['extension'];
			$file_location = $dir . $name;
			$this->_final_name = $name;
			move_uploaded_file($this->_file['path'], $file_location);
			return true;
		}
		return false;
	}

	public function filename() {
		return $this->_final_name;
	}

	public function insert_file_into_database()
	{
		
	}

	public function set_user($userid = NULL)
	{
		$this->user = $this->_CI->auth->user($userid)->row();
	}

	public function set_owner($userid = NULL)
	{
		$this->owner = $this->_CI->auth->user($userid)->row();
		$this->ownerdir(true);
	}

	public function files_left()
	{
		if($this->owner->file_limit < 1) $filesleft = $this->_config['default_file_limit'] - $this->get_content_info()['filenumber'];
		else $filesleft = $this->owner->file_limit - $this->get_content_info()['filenumber'];
		if($filesleft > 0) return $filesleft;
		return 0;
	}

	public function folders_left()
	{
		if($this->owner->folder_limit < 1) $foldersleft = $this->_config['default_folder_limit'] - $this->get_content_info()['dirnumber'];
		else $foldersleft = $this->owner->folder_limit - $this->get_content_info()['dirnumber'];
		if($foldersleft > 0) return $foldersleft;
		return 0;
	}

	public function get_content_info($dir = NULL)
	{
		$this->_dir($dir);
		if(!isset($this->_dirinfo[$dir])) {
			$this->get_dir_content($dir);
		}
		return $this->_dirinfo[$dir]['info'];
	}

	public function get_dir_content($dir = NULL, &$contentinfo = array(), $optimizeJSON = false)
	{
		$this->_dir($dir);
		$this->_parse_dir($dir, FALSE);
		if(isset($this->_dirinfo[$dir]))
		{
			$contentinfo = $this->_dirinfo[$dir]['info'];
			return $this->_dirinfo[$dir]['content'];
		}
		if(!isset($contentinfo) || !is_array($contentinfo)) $contentinfo = array();
		if(!isset($contentinfo['dirnumber']) || !is_int($contentinfo['dirnumber'])) $contentinfo['dirnumber'] = 0;
		if(!isset($contentinfo['filenumber']) || !is_int($contentinfo['filenumber'])) $contentinfo['filenumber'] = 0;
		if(!is_dir($dir)) return false;
		$content = scandir($dir);
		$return = array("dirs" => array(), "files" => array());
		foreach($content as $v)
		{
			if($v == '.' || $v == '..') continue;
			if(is_dir($dir . $v)) {
				$contentinfo['dirnumber']++;
				$return['dirs'][$v] = $this->get_dir_content($dir . $v, $contentinfo);
				if(count($return['dirs'][$v]) == 0 && $optimizeJSON == true) $return['dirs'][$v] = new stdClass;
			} else {
				$contentinfo['filenumber']++;
				$return['files'][$v] = filesize($dir . $v);
			}
		}
		$content = array_merge($return['dirs'], $return['files']); // This way dirs will always be displayed first, at top.
		$this->_dirinfo[$dir] = array('content' => $content, 'info' => $contentinfo);
		return $content;
	}

	public function set_to_owner_dir()
	{
		if(isset($this->owner->unique_id))
		{
			$this->set_dir($this->_config['dir']['user'] . $this->owner->unique_id);
		} else {
			return false;
		}
	}

	public function set_dir($dir)
	{
		$this->_parse_dir($dir);

		$this->_dir['filecontroller'] = $this->_config['location']['internal'] . $dir;
		$this->_dir['base']	= $this->_config['location']['external'] . $dir;

		$this->_dir['internal'] = $this->_config['location']['internal'] . $dir;
		$this->_dir['external'] = $this->_config['location']['external'] . $dir;
		$this->_dir['relative'] = $dir;
	}

	public function get_dir($type = NULL)
	{
		switch (strtolower($type))
		{
			case 'fc':
			case 'filecontroller':
			case 'internal':
			return $this->_dir['internal'];
			case 'base':
			case 'base_url':
			case 'external':
			return $this->_dir['external'];
			default:
			return $this->_dir['relative'];
		}
	}

	public function is_valid_dir_name($name)
	{
		if(preg_match($this->_config['dir_regex'], $name, $matches) && $matches[0] == $name){
			return true;
		}
		return false;
	}

	public function is_valid_parent_dir_name($name)
	{
		if(preg_match($this->_config['parent_dir_regex'], $name, $matches) && $matches[0] == $name){
			return true;
		}
		return false;
	}

	public function is_valid_file_name($name)
	{
		if(preg_match($this->_config['dir_regex'], $name, $matches) && $matches[0] == $name){
			return true;
		}
		return false;
	}

	public function create_sub_dir($name, $parent = false)
	{
		$this->_parse_dir($parent, false, true);
		if(!$this->is_valid_parent_dir_name($parent)) return false;
		if($this->folders_left() != false)
		{
			if($this->is_valid_dir_name($name))
			{
				$name = $parent . $name;
				if(is_dir($this->get_dir('internal') . $name))
				{
					return array("success" => false, "message" => "A folder with this already exists.");
				}
				if(file_exists($this->get_dir('internal') . $name))
				{
					return array("success" => false, "message" => "A file with this name already exists, delete the file first to create a folder with this name.");
				}
				mkdir($this->get_dir('internal') . $name);
				return array("success" => true);
			} else {
				return array("success" => false, "message" => array($this->_config['dir_regex_fail']));
			}
		} else {
			return array("success" => false, "message" => "You have too much folders to create a new one. Please delete a folder first or ask an admin.");
		}
	}

	private function _dir(&$dir = NULL)
	{
		if(!$this->_dir['internal'] || !$this->_dir['external'] || !$this->_dir['relative'])
		{
			if(isset($this->owner->unique_id)) {
				$this->set_to_owner_dir();
			} else {
				trigger_error("No file-direcotry & no owner defined.", E_USER_ERROR);
				exit();
			}
		}
		if(!is_string($dir)) $dir = $this->_dir['internal'];
	}

	private function _parse_dir(&$dir, $beginning = true, $ending = true)
	{
		if(strlen($dir)>0)
		{
			if($beginning && $dir[0] == '/')              $dir = substr($dir, 1);
			if($ending    && $dir[strlen($dir)-1] != '/') $dir .= '/';
		}
	}

	public function __call($name, $arguments)
	{
		if (!method_exists($this->_CI->fileinfo, $name) )
		{
			trigger_error('Undefined method Data_lib::' . $name . '() called.', E_USER_WARNING);
			return;
		}
		return call_user_func_array( array($this->_CI->fileinfo, $name), $arguments);
	}
}
?>