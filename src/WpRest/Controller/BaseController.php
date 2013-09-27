<?php namespace WpRest\Controller;

use WpRest\Manager\ResponseObject;

/**
 * Base Controller Class
 *
 * Extends all useful things.
 *
 * @package  wprest
 * @subpackage controller
 */
abstract class BaseController {
	protected $response;
	protected $request;
	protected $introspector;
	
	/**
	 * Setup the new Controller
	 *
	 * @uses  do_action() Calls `wp-rest-api-controller-construct` with this object
	 */
	public function __construct()
	{
		$a = \WpRest\Manager\Application::Instance();

		$this->response = new ResponseObject;
		$this->request = $a->request;
		$this->introspector = $a->introspector;

		do_action('wp-rest-api-controller-construct', $this);
	}

	/**
	 * Make a Error response
	 * 
	 * @param integer
	 * @param  string
	 * @return object
	 */
	protected function error($statusCode, $message = null)
	{
		return \WpRest\Manager\Application::applicationError($statusCode, $message);
	}
}