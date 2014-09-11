<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Data extends CI_Controller {

	var $user 	= FALSE;
	var $owner 	= FALSE;
	public function __construct() {
		parent::__construct();
		$this->load->library('ion_auth');
		$this->load->model('subgroup_model');
		$this->load->database();
		$this->load->library('data_lib');
		$this->load->library('template');
	}

	public function api($command = FALSE, $ownerid = FALSE) {
		$this->user = $this->ion_auth->user()->row();
		$this->output->set_content_type('application/json');
		if(!$this->user) {
			$this->output->set_output(json_encode(array(
				'access' => false,
				'messages' => array('You have to be logged in to view the files.'),
				)));
			return;
		}


		if(!$ownerid || $ownerid == $this->user->id) {
			$this->owner = $this->user;
		} else {
			$this->owner = $this->ion_auth->user($ownerid)->row();
		}
		if(!$command)
		{
			$command = 'list';
		}

		$this->data_lib->user  = $this->user;
		$this->data_lib->owner = $this->owner;

		if($this->owner->id == $this->user->id)
		{
			if(method_exists($this, '_api_' . $command))
			{
				call_user_func(array($this, '_api_' . $command));
			} else {
				$this->output->set_output(json_encode(array(
				'access' => false,
				'messages' => array('This api-link does not result in anything.'),
				)));
			}
		}
		else 
		{
			$this->output->set_output(json_encode(array(
				'access' => false,
				'messages' => array('You have no access to view this page. Only the owner can visit the files.'),
				)));
		}

	}

	private function _api_list()
	{
		$output = array(
			"base" 		=> $this->data_lib->get_dir('external'), 
			"content" 	=> $this->data_lib->get_dir_content(false, $info, true),
			"uploadleft" => array(
				"files" => $this->data_lib->files_left(),
				"folders" => $this->data_lib->folders_left()
				)
		);
		$this->output->set_output(json_encode($output));
	}

	private function _api_createdir()
	{
		$this->output->set_output(json_encode($this->data_lib->create_sub_dir($this->input->post('dir'), $this->input->post('parent_dirs'))));
	}
	
	private function _api_upload()
	{
		$type = $this->input->post('type');
		if(method_exists($this, '_api_upload_' . $type))
		{
			call_user_func(array($this, '_api_upload_' . $type));
		}
		else
		{
			$this->output->set_output(json_encode(array(
				'success' => false,
				'messages' => array(
					'This upload type does not exist.'
				)
			)));
		}
	}

	private function _api_upload_image()
	{
		$this->data_lib->set_file($_FILES['file']);
		if($type = $this->data_lib->is_file_type("image"))
		{
			if($this->data_lib->move_file($this->data_lib->ownerdir() . $this->input->post('directory')))
			{
				$this->output->set_output(json_encode(array(
					'success' => true,
					'messages' => array(
						'File successfully uploaded.'
						),
					'filesize' => $this->data_lib->filesize(),
					'filename' => $this->data_lib->filename()
					)
				)
				);
			} else {
				$this->output->set_output(json_encode(array(
					'success' => false,
					'messages' => array(
						'The folder for the file does not exist.'
						)
					)
				));
			}
		} else {
			$this->output->set_output(json_encode(array(
				'success' => false,
				'messages' => array(
					'The uploaded file is not a valid image file.'
				)
			)
			));
		}
	}

	private function _api_upload_file()
	{
		$this->_api_upload_image();
	}
}