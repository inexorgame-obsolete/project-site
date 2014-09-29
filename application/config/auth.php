<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['require_email_verification'] = false;	// Require E-Mail verification - currently not implemented
$config['disallowed_characters'] = array('@', ' ');
$config['username_regex'] = '/(.*)/';
$config['username_min_length'] = 1;				// Max length is DB-field-size.
$config['password_min_length'] = 6;
$config['stay_logged_in_time'] = 2678400;		// will be added to time();

// You can insert %s in the messages. %s will be an escaped string. 
$config['error_messages'] = array(
	'invalid_email' 					=> 'Your E-Mail is invalid. Please enter a valid E-Mail.',
	'email_exists'						=> 'An account with your E-Mail already exists. You can only register with the E-Mail once.',	// %s = E-Mail
	'username_exists'					=> 'An username with this name already exists. Please choose an other one.',					// %s = Username
	'username_disallowed_char' 			=> 'You are using a disallowed char in your name. The char %s is not allowed.',					// %s = disallowed char
	'username_disallowed_chars'			=> 'You are using disallowed chars in your name. The chars %s are not allowed.',				// %s = disallowed chars (with "and" before last char)
	'username_regex_disallowed_char'	=> 'The char %s is not allowed.', 																// %s = disallowed char
	'username_regex_disallowed_chars'	=> 'The chars %s are not allowed.',																// %s = disallowed chars (with "and" before last char)
	'username_too_short'				=> 'The username is too short. It have to be between %s and %s characters long.',				// 1st %s = username min-length; 2nd %s = username max length
	'username_too_long'					=> 'The username is too long. It should not exceed %s characters and should have at least %s',	// 1st %s = username max length; 2nd %s = username max length'
	'password_too_short'				=> 'The password should have at least %s characters.',											// %s = password min-length
	'passwords_do_not_match'			=> 'The password you submitted does not match your verification.',
	'wrong_captcha'						=> 'The captcha you submitted is wrong.'
);