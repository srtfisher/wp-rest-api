<?php namespace JsonApi\Controller;

abstract class BaseController {
	/**
	 * Manipulate the response
	 *
	 * Override this for each custom use case
	 * 
	 * @param mixed
	 * @param integer
	 * @return string
	 */
	public function response($data = array(), $http_code = 200)
	{
		return json_encode($data);
	}
}