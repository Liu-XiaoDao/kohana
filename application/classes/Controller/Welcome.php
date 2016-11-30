<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {

	public function action_index()
	{
		$this->response->body('hello, world!');
        Kohana::$config->load('url');
        Kohana::$config->load('curl');
	}

} // End Welcome
