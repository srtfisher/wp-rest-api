<?php namespace WpRest\Manager;

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
		$settings = Settings::Instance();
		$base = (isset($settings->base)) ? $settings->base : 'api';
		$request = Application::Instance()->request;
		$request_url = $request->server->get('REQUEST_URI');

		if (strpos($request_url, '?')) :
			$explodeRequest = explode('?', $request_url);
			$request_url = $explodeRequest[0];
		endif;

		preg_match('~/api/?(.*?)$~', $_SERVER['REQUEST_URI'], $this->rawRequest);

		$this->requestStructured = array();
		$explode = explode('/', $this->rawRequest[1], 3);
		$controller = $method = $arguments = '';

		$count = count($explode);

		// Resource Controller Setup
		if ($count == 2 AND is_numeric( $explode[1] )) :
			$explode[2] = $explode[1];
			$explode[1] = 'single';
		endif;

		// Some hard logic.
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
		var_dump($this->requestStructured);exit;
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

	/**
	 * Get the Controller
	 *
	 * @return  string
	 */
	public function getController()
	{
		return strtolower($this->requestStructured['controller']);
	}

	/**
	 * Get the Method
	 *
	 * @return  string
	 */
	public function getMethod()
	{
		$input = ucfirst(strtolower($this->requestStructured['method']));
		$input = str_replace('-', '_', $input);
		
		// Get the request
		$request = Application::Instance()->request;
		$method = strtolower($request->server->get('REQUEST_METHOD'));
		return $method.$input;
	}

	/**
	 * Get the Arguments
	 *
	 * @return array
	 */
	public function getArguments()
	{
		return (array) $this->requestStructured['arguments'];
	}
}