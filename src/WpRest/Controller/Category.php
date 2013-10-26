<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

class Category extends BaseController implements ControllerInterface {
	public $base = 'categories';

	public function controllerInfo()
	{
		return array(
			'name' => 'Categories',
			'description' => 'Data manipulation methods for categories'
		);
	}

	/**
	 * GET /tags
	 */
	public function getIndex() {
		$introspector = Application::Instance()->introspector;
		$categories = $introspector->get_categories();

		return $this->response->json(array(
			'count' => count($categories),
			'categories' => $categories
		));
	}

	/**
	 * Create new tag
	 * 
	 * POST /tags
	 */
	public function postIndex()
	{

	}
}