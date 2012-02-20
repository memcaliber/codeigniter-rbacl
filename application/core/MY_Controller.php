<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
	
	public function __construct()
	{
		parent::__construct();
		// load the RB-ACL library
		$userId	= 0;
		$this->load->library('rbacl',$userId);
	}
	
}
