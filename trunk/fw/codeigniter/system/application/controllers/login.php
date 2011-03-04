<?php

class Login extends Controller {
	
	function index()
	{
		$this->load->helper(array('form', 'url'));
		
		$this->load->library('form_validation');

		$this->form_validation->set_rules('username', 'Username', 'required|min_length[4]|max_length[12]');
		$this->form_validation->set_rules('password', 'Password', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('overview_login');
		}
		else
		{
			$this->load->view('overview');
		}
	}
}
?>