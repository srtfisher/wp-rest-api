<?php namespace JsonApi\Manager;

class Router {
	protected $rawRequest;
	public $base = 'api';
	protected $requestStructured;

	public function __construct()
	{
		$this->runMatches();
	}

	public function runMatches()
	{
		preg_match('~/api/?(.*?)$~', $_SERVER['REQUEST_URI'], $this->rawRequest);

		$this->requestStructured = array();
		$explode = explode('/', $this->rawRequest[1], 3);
		$controller = $method = $arguments = '';

		$count = count($explode);
		if ($count == 0 OR ($count == 1 AND $explode[0] == '')) :
			$controller = $this->defaultController();
			$method = 'index';
		elseif ($count ==1 OR ($count == 2 AND $explode[1] == '')) :
			$controller = $explode[0];
			$method = 'index';
		else :
			$controller = $explode[0];
			$method = $explode[1];
			$arguments = (isset($explode[2])) ? $explode[2] : '';
		endif;

		if ($arguments !== '')
			$arguments = explode('/', $arguments);

		$this->requestStructured = compact('controller', 'method', 'arguments');
	}

	/**
	 * Determine if the current request is an API Request
	 * 
	 * @return boolean
	 */
	public function isApiRequest()
	{
		return (count($this->rawRequest) > 0);
	}

	/**
	 * Default Controller
	 * 
	 * @return string
	 */
	protected function defaultController()
	{
		return apply_filters('wp-rest-api-default-controller', 'core');
	}
}