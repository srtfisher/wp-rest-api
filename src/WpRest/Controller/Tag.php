<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

class Tag extends BaseController implements ControllerInterface {
	public $base = 'tags';

	public function controllerInfo()
	{
		return array(
			'name' => 'Tags',
			'description' => 'Data manipulation methods for tags'
		);
	}

	
	/**
	 * Category listing
	 * 
	 * GET /categories
	 */
	public function getIndex()
	{
		$tags = $this->introspector->get_tags();
		return $this->response->json(array(
			'count' => count($tags),
			'tags' => $tags
		));
	}

	/**
	 * Create category
	 *
	 * POST /tags
	 */
	public function postIndex()
	{

	}
}