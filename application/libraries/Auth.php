<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Auth
{
	private $_stay_logged_in;
	private $_CI;
	private $_require_email_verification = 0;
	private $_disallowed_characters;
	private $_language_and = 'and';
	public function __construct() {
		$this->_CI =& get_instance();
		$this->_CI->load->model('users_model');
		$this->_CI->load->model('users_stay_logged_in_model');
		$this->_CI->load->helper('email');
		$this->_CI->load->config('auth');
		// CodeIgniter's session-class will become a driver in CI 3
		if (substr(CI_VERSION, 0, 1) == '2') $this->_CI->load->library('session');
		else $this->_CI->load->driver('session');

		$this->_require_email_verification 	= $this->_CI->config->item('require_email_verification');
		$this->_disallowed_characters 		= $this->_CI->config->item('disallowed_characters');
		$this->_username_regex 				= $this->_CI->config->item('username_regex');
		$this->_stay_logged_in = $this->_CI->config->item('stay_logged_in_time');

	}

	public function login($username, $password, $stay_logged_in = false) {
		if(strpos($username, "@") !== false) {
			// checks email, not username
			if(isint($id = $this->_CI->users_model->check_password($username, $password, true, true))  && $id !== 0) {
				return $this->_login($id, $stay_logged_in);
			}
		}
		if(isint($id = $this->_CI->users_model->check_password($username, $password)) && $id !== 0)
		{
			return $this->_login($id, $stay_logged_in);
		}
		return false;
	}

	public function register_user($email, $username, $password, $password_verification = false, $captcha = NULL) {
		$error_messages = $this->_CI->config->item('error_messages');
		$hit_disallowed_characters = array();
		$error = array();
		if(!valid_email($email)) $error['invalid_email'] = $error_messages['invalid_email'];
		if(count($this->_CI->users_model->user_by_email($email)) > 0) $error['email_exists'] = sprintf($error_messages['email_exists'], htmlentities($email));
		if(count($this->_CI->users_model->user_by_username($username)) > 0) $error['username_exists'] = sprintf($error_messages['username_exists'], htmlentities($username));
		for($i = 0; $i < strlen($username); $i++) {
			if(in_array($username[$i], $this->_disallowed_characters)) {
				$hit_disallowed_characters[] = $username[$i];
			}
		}
		$dis_chars_count = count($hit_disallowed_characters);
		if($dis_chars_count == 1) {
			$error['username_disallowed_char'] = sprintf($error_messages['username_disallowed_char'], '\'' . $hit_disallowed_characters[0] . '\'');
		} elseif($dis_chars_count > 1) {
			$last_dis_char = $hit_disallowed_characters[$dis_chars_count-1];
			unset($hit_disallowed_characters[$dis_chars_count-1]);
			$replace = '\'' . implode('\', \'', $hit_disallowed_characters) . '\'';
			$replace .= ' ' . $this->_language_and . ' \'' . $last_dis_char . '\'';
			$error['username_disallowed_chars'] = sprintf($error_messages['username_disallowed_chars'], $replace);
		}
		$match = preg_match($this->_username_regex, $username, $matches);
		if(!$match || $matches[0] != $username) {
			$hit_disallowed_characters = $this->_return_non_matching_characters($this->_username_regex, $username);
			$dis_chars_count = count($hit_disallowed_characters);
			if($dis_chars_count == 1) {
				$error['username_regex_disallowed_char'] = sprintf($error_messages['username_regex_disallowed_char'], '\'' . $hit_disallowed_characters[0] . '\'');
			} elseif($dis_chars_count > 1) {
				$last_dis_char = $hit_disallowed_characters[$dis_chars_count-1];
				unset($hit_disallowed_characters[$dis_chars_count-1]);
				$replace = '\'' . implode('\', \'', $hit_disallowed_characters) . '\'';
				$replace .= ' ' . $this->_language_and . ' \'' . $last_dis_char . '\'';
				$error['username_regex_disallowed_chars'] = sprintf($error_messages['username_regex_disallowed_chars'], $replace);
			}
		}
		$username_max_length = $this->_CI->users_model->max_username_length();
		if(strlen($username) < $this->_CI->config->item('username_min_length')) {
			$error['username_too_short'] = sprintf($error_messages['username_too_short'], $this->_CI->config->item('username_min_length'), $username_max_length);
		}
		if(strlen($username) > $username_max_length) {
			$error['username_too_long'] = sprintf($error_messages['username_too_long'], $username_max_length, $this->_CI->config->item('username_min_length'));
		}
		if(strlen($password) < $this->_CI->config->item('password_min_length')) {
			$error['password_too_short'] = sprintf($error_messages['password_min_length'], $this->_CI->config->item('password_min_length'));
		}
		if($password_verification !== false && $password_verification != $password) {
			$error['passwords_do_not_match'] = $error_messages['passwords_do_not_match'];
		}
		if($captcha !== null && $captcha != true) {
			$error['wrong_captcha'] = $error_messages['wrong_captcha'];
		}


		if(count($error) > 0) return $error;
		
		$this->_CI->users_model->create($email, $username, NULL, $password, ip(), NULL, $this->_require_email_verification);
		if($this->_require_email_verification == true)
		{
			// $this->send_registration_email();
		}
		return true;
	}

	public function send_registration_email() {
		$this->_CI->load->library('email');
		$this->_CI->email->from('');
		$this->_CI->email->to('');
		$this->_CI->email->subject('Email test');
		$this->_CI->email->message('Testing the email class');

		$this->_CI->email->send();
	}

	public function user($id = false) {
		if(isint($id)) {
			return $this->_CI->users_model->user($id);
		} else {
			if(!$this->is_logged_in()) return false;
			$userid = $this->_CI->session->userdata('userid');
			if(isint($userid) && $this->_CI->session->userdata('user_unique_id')) {
				$user = $this->user($userid);
				if($user->unique_id == $this->_CI->session->userdata('user_unique_id')) {
					return $user;
				}
			}
		}
		return false;
	}

	public function is_logged_in() {
		if(isint($this->user_session())) return true;
		$uid = $this->_CI->input->cookie('user_id');
		$ucd = $this->_CI->input->cookie('user_code');
		if($this->_CI->users_stay_logged_in_model->check($uid, $ucd, true)) {
			$this->set_session($uid);
			return true;
		}
		return false;
	}
	public function logged_in() {
		return $this->is_logged_in();
	}

	public function user_session() {
		$userid = $this->_CI->session->userdata('userid');
		if(isint($userid) && $this->_CI->session->userdata('user_unique_id')) {
			$user = $this->user($userid);
			if($user->unique_id == $this->_CI->session->userdata('user_unique_id')) {
				return $userid;
			}
		}
		return false;
	}

	public function logout() {
		$this->_CI->session->unset_userdata(array('userid', 'user_unique_id'));
		$this->_CI->session->sess_destroy();
		$this->_CI->users_stay_logged_in_model->remove($this->_CI->input->cookie('user_id'), $this->_CI->input->cookie('user_code'));
		$this->_CI->input->set_cookie(array(
			'name' 	=> 'user_id',
			'value' => false,
			'expire' => time()-100,
			'secure' => false
		));
		$this->_CI->input->set_cookie(array(
			'name' 	=> 'user_code',
			'value' => false,
			'expire' => time()-100,
			'secure' => false
		));
	}

	public function set_session($uid) {
		if(!isint($uid)) return false;
		$user = $this->user($uid);
		$newdata = array('userid' => $uid, 'user_unique_id' => $user->unique_id);
		$this->_CI->session->set_userdata($newdata);
		return true;
	}

	private function _return_non_matching_characters($regex, $text) {
		$array = array();
		$text_length = strlen($text);
		for($i = 0; $i < $text_length; $i++) {
			$new_text = str_pad(str_pad($text[$i], $i, 'a'), $text_length, 'a');
			preg_match($regex, $new_text, $new_matches);
			if($new_matches[0] != $new_text && !in_array($text[$i], $array)) {
				$array[] = $text[$i];
			}
		}
		return $array;
	}

	private function _login($uid, $stay_logged_in = false, $expiration = false) {
		if(!isint($expiration)) $expiration = $this->_stay_logged_in;
		if(isint($uid)) {
			$user = $this->user($uid);
			$this->set_session($uid);
		}

		if($stay_logged_in) {
			$code = $this->_CI->users_stay_logged_in_model->insert($uid);
			$id_cookie = array(
				'name' 	=> 'user_id',
				'value' => $uid,
				'expire' => $expiration,
				'secure' => true
			);
			$code_cookie = array(
				'name' => 'user_code',
				'value' => $code,
				'expire' => $expiration,
				'secure' => true
			);
			if(ENVIRONMENT != 'production')
			{
				$id_cookie['secure'] = false;
				$code_cookie['secure'] = false;
			}
			$this->_CI->input->set_cookie($id_cookie);
			$this->_CI->input->set_cookie($code_cookie);
		}

		return true;
	}
}


