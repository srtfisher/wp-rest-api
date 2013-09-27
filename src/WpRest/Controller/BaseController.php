<?php namespace WpRest\Controller;

use WpRest\Manager\ResponseObject;

abstract class BaseController {
	protected $response;

	public function __construct()
	{
		$this->response = new ResponseObject;
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
}