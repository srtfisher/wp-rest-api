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
	
	/**
	 * Setup the new Controller
	 *
	 * @uses  do_action() Calls `wp-rest-api-controller-construct` with this object
	 */
	public function __construct()
	{
		$this->response = new ResponseObject;
		$this->request = \WpRest\Manager\Application::Instance()->request;

		do_action('wp-rest-api-controller-construct', $this);
	}

	/**
	 * Make a simple response
	 * 
	 * @param string
	 * @param  integer
	 * @param  array
	 * @return object
	 */
	protected function response($data, $statusCode = 200, array $headers = array())
	{
		return Response::make($data, $statusCode, $headers);
	}

	/**
	 * Make a JSON response
	 * 
	 * @param mixed
	 * @param  integer
	 * @param  array
	 * @return object
	 */
	protected function json($data, $statusCode = 200, array $headers = array())
	{
		return Response::json($data, $statusCode, $headers);
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