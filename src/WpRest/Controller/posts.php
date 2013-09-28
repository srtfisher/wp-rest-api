<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

class Posts extends PostBase implements ControllerInterface {
	protected $type = 'post';
	public $base = 'posts';

	public function controllerInfo()
	{
		return array(
			'name' => 'Posts',
			'description' => 'Data manipulation methods for posts'
		);
	}
}