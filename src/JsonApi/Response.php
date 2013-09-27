<?php namespace JsonApi;

use Symfony\Component\HttpFoundation\BinaryFileResponse,
	Symfony\Component\HttpFoundation\Response as ResponseBase;

class Response extends ResponseBase
{
	/**
	 * Return a new JSON response from the application.
	 *
	 * @param  string|array  $data
	 * @param  int    $status
	 * @param  array  $headers
	 */
	public static function json($data = array(), $status = 200, array $headers = array())
	{
		if (is_object($data) AND method_exists($data, 'toArray'))
			$data = $data->toArray();

		$data = json_encode($data);

		$response = new self($data, $status, $headers);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	/**
	 * Create a new file download response.
	 *
	 * @param  SplFileInfo|string  $file
	 * @param  string  $name
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public static function download($file, $name = null, array $headers = array())
	{
		$response = new BinaryFileResponse($file, 200, $headers, true, 'attachment');

		if ( ! is_null($name))
		{
			return $response->setContentDisposition('attachment', $name);
		}

		return $response;
	}

	/**
	 * Make a Response
	 *
	 * @param  string Content
	 * @param  integer Status Code
	 * @param  array Headers
	 */
	public static function make($contents, $statusCode = 200, array $headers = array())
	{
		$response = new self($contents, $statusCode, $headers);
		return $response;
	}
}