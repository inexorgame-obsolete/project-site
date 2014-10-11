<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function data($file)
{
	if(file_exists(FCPATH . 'data/' . $file))
	{
		return base_url() . 'data/' . $file;
	}
	return false;
}
function css($file) { return data('css/' . $file . '.css'); }
function dcss($file) {
	if($file = css($file)) 
	{
		echo '<link rel="stylesheet" type="text/css" href="' . $file . '" />' . "\n";
		return true;
	}
	return false;
}
function js($file) { return data('js/' . $file . '.js'); }
function image($file) {	return data('images/' . $file); }
function iimage($file, $userimages = false, $types = array('.jpg', '.jpeg', '.png', '.gif')) {
	switch($userimages) {
		case 1:
			$dir = 'users/avatar/';
			break;
		case 'avatar':
			$dir = 'users/avatar/';
			break;
		case 2:
			$dir = 'users/background/';
			break;
		case 'background':
			$dir = 'users/background/';
			break;
		default:
			$dir = 'images/';

	}
	foreach($types as $type)
	{
		$return = data($dir . $file . $type);
		if($return !== false) return $return;
	}
	if($userimages == 1 || $userimages == 'avatar') return call_user_func(__FUNCTION__, 'no-avatar', 1);
	return false;
}
function avatar_image($id) {
	if($i = iimage($id, 1)) { return $i; }
	return data('users/avatar/no-avatar.png');
}
function background_image() {

}
function showname($user, $class = "user") {
	if(!is_array($user)) $user = (array) $user;
	$return = '<span class="' . $class . '">';
	if(strlen($user['ingame_name']) < 1)
		$return .= d($user['username']);
	else
		$return .= d($user['ingame_name']);
	$return .= '</span>';
	return $return;
}
function h($string) { return htmlentities($string); }
function he($string) {
	echo h($string);
	return;
}
function ph($string) 
{
	return p_r(h($string));
}
function link_links($string) {
	return preg_replace("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", '<a target="_blank" href="$0">$0</a>', $string);
}
function prevent_replace($string) { return str_replace(array("{", "}"), array("{<", ">}"), $string); }
function p_r($string) { return prevent_replace($string); }
function d($string, $nl2br = false) {
	$return = p_r(h($string));
	if($nl2br) return nl2br($return);
	return $return;
}
function dt($date) {
	return 'on ' . date('j\<\s\u\p\>S\<\/\s\u\p\> \o\f F Y', strtotime($date)); // return somethint like 1st of January 2014
}
function tm($time){
	return 'at ' . date('H:i:s', strtotime($time));
}
function dt_tm($time) {
	return dt($time) . ' ' . tm($time);
}