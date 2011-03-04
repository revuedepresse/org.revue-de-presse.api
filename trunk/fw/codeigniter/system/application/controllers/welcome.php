<?php

class Welcome extends Controller {

	function Welcome()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules(
			'username',
			'Username',
			'required|min_length[4]|max_length[12]|callback_memberExists'
		);

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

	function memberExists($str)
	{
		$errors = Member::memberExists($str, $this->form_validation->set_value("password"));

		if (count($errors) == 0)
		
			return true;
		
		if ($errors[0])

			$this->form_validation->set_message('password', 'incorrect password');
		
		if ($errors[1])

			$this->form_validation->set_message('username', 'We can not find this user name in our database');

		return false;
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */