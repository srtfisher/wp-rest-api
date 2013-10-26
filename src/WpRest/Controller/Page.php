<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

class Page extends PostBase implements ControllerInterface {
	protected $type = 'page';
	public $base = 'pages';

	public function controllerInfo()
	{
		return array(
			'name' => 'Pages',
			'description' => 'Data manipulation methods for pages'
		);
	}
}