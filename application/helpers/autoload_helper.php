<?php
if(!function_exists('isint')) {
	function isint(&$val) {
		if(ctype_digit($val) || is_int($val)) {
			$val = (int) $val;
			return true;
		}
		return false;
	}
}
if(!function_exists('ip')) {
	function ip() {
		if($_SERVER['HTTP_X_FORWARDED_FOR']){
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else { 
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}