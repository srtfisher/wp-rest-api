<?php namespace WpRest\Manager;

use WpRest\Response;

/**
 * Simple Object Interface to interact with response
 *
 * Useful to simply the life of a Controller
 *
 * @package  wprest
 * @package  response
 */
class ResponseObject {
	public function json($data, $statusCode = 200, array $headers = array())
	{
		return Response::json($data, $statusCode, $headers);
	}

	public function make($data, $statusCode = 200, array $headers = array())
	{
		return Response::make($data, $statusCode, $headers);
	}
}