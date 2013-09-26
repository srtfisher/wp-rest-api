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

	/**
	 * Human Readable method names
	 *
	 * @param  string name
	 */
	public function humanMethodName($name)
	{
		switch ($name)
		{
			case 'index' :
				$r = 'GET %s/%s/';
				break;

			case 'store' :
				$r = 'POST %s/%s/';
				break;

			default :
				$methods = array(
					'put',
					'post',
					'delete',
					'get',
				);
				break;
				$r = $name;
		}

		return $r;
	}
}