<?php
if(!function_exists('isint')) {
	function isint(&$val, $fallback = false) {
		if(ctype_digit($val) || is_int($val)) {
			$val = (int) $val;
			return true;
		}
		if(is_int($fallback))
		{
			$val = $fallback;
		}
		return false;
	}
}
if(!function_exists('ip')) {
	function ip() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
		return $_SERVER['REMOTE_ADDR'];
	}
}
if(!function_exists('hash_available')) {
	function hash_available($hash) {
		$algos = hash_algos();
		if(in_array(strtolower($hash), $algos)) return true;
		return false;
	}
}
if(!function_exists('mail_host')) {
	function mail_host() {
		if(strpos(':', $_SERVER['HTTP_HOST']) === false && strpos('/', $_SERVER['HTTP_HOST']) === false)
		{
			return $_SERVER['HTTP_HOST'];
		}
		$url = parse_url($_SERVER['HTTP_HOST']);
		return $url['host'];
	}
}