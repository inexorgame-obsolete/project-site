<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fileinfo {
	public $magicnumbers;
	public $filepath;
	public $dir;
	public $basename;
	public $extension;
	public $filename;
	private $_CI;
	private $_maxlength;
	private $_filecontent;
	private $_filesize;
	private $_initalized = false;

	public function __construct()
	{
		$this->_CI =& get_instance();
		$this->_CI->load->config('magicnumbers');
		$this->magicnumbers = $this->_CI->config->item('magicnumbers');
	}

	public function init()
	{
		if(!is_string($this->filepath)) throw new Exception("No file set.");
		$this->_initalized = $this->filepath;
		$this->_filesize = filesize($this->filepath);
		$maxlength = $this->getLongestMagicNumber();
		$this->_maxlength = ($maxlength > $this->_filesize) ? $this->_filesize : $maxlength ;
		$handle = fopen($this->filepath, "r");
		$contents = fread($handle, $this->_maxlength);
		fclose($handle);
		$file = '';
		for($i = 0; $i < strlen($contents); $i++) {
			$f = dechex(ord($contents[$i]));
			if(strlen($f) == 1) $file .= 0;
			$file .= dechex(ord($contents[$i]));
		}
		$file = strtoupper($file);
		$this->_filecontent = $file;
		$pathinfo = pathinfo($this->filepath);
		$this->extension = $pathinfo['extension'];
		$this->basename = $pathinfo['basename'];
		$this->dir = $pathinfo['dirname'];
		$this->name = $pathinfo['filename'];
	}

	public function get_real_file_type($magicnumbers = false) {
		$this->checkInit();
		$results = array();
		if(!is_array($magicnumbers)) $magicnumbers = $this->magicnumbers;
		foreach($magicnumbers as $k => $v) {
			unset($magicnumber);
			$offset = 0;
			if(is_array($v)) {
				foreach($v as $kk => $vv) {
					if(is_int($kk)) {
						$magicnumber = $vv;
					} else {
						$magicnumber = $kk;
						$offset = $vv * 2; // $vv is in Bytes, but $offset is needed for 4 bit blocks
					}
				}
			} else {
				$magicnumber = $v;
			}
			for($i = 0; $i < strlen($magicnumber); $i++) {
				if($this->_filecontent[$i+$offset] != $magicnumber[$i] && ($magicnumber[$i] != 'n' || !is_numeric($this->_filecontent[$i+$offset])) && $magicnumber[$i] != 'x') break; // So if magicnumber does not match with content AND magicnumber is not n while content is numeric AND magicnumber is not x: break
				if(!isset($magicnumber[$i+1])) $results[] = $k;
			}
		}
		return $results;
	}

	public function is_file_type($types) {
		$this->checkInit();
		if(is_string($types)) $types = (array) $types;
		$magicnumbers = array();
		foreach($types as $t) {
			if(isset($this->magicnumbers[$t])) {
				$magicnumbers[$t] = $this->magicnumbers[$t];
			}
		}
		$result = $this->get_real_file_type($magicnumbers);
		if(count($result)==0) return false;
		return $result;
	}

	public function check_type_available($type) {
		$this->checkInit();
		if(isset($this->magicnumbers[$type])) return true;
		return false;
	}

	public function type_matches_extension() {
		$this->checkInit();
		if($this->check_type_available($this->extension) == false) {
			trigger_error('There is not a file signature set for the filetype: ' . $this->extension, E_USER_NOTICE);
		}
		return $this->is_file_type($this->extension);
	}

	public function filesize() {
		return $this->_filesize;
	}

	private function checkInit() {
		if($this->_initalized == false || $this->_initalized != $this->filepath) $this->init();
	}

	private function getLongestMagicNumber($including_offset = true) {
		$longest = 0;
		foreach($this->magicnumbers as $k => $v) {
			if(is_array($v)) {
				foreach($v as $kk => $vv) {
					if(is_int($kk)) {
						if($longest < strlen($vv)) {
							$longest = strlen($vv) / 2;
							continue;
						}
					} else {
						if($including_offset == true) {
							if($longest < strlen($kk) + $vv) {
								$longest = strlen($kk) / 2 + $vv;
								continue;
							}
						} else {
							if($longest < strlen($kk)) {
								$longest = strlen($kk) / 2;
								continue;
							}
						}
					}
				}
			} else {
				if($longest < strlen($v)) {
					$longest = strlen($v);
				}
			}
		}
		return $longest;
	}
}