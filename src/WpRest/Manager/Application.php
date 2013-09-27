<?php namespace WpRest\Manager;

use Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	WpRest\Manager\Collection,
	WpRest\Manager\Router;

/**
 * JSON API Manager
 *
 * @package  WpRest
 */
class Application {
	/**
	 * Static Instance Store
	 * 
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Request Object
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	public $request;

	/**
	 * Introspector
	 *
	 * @var  WpRest\Manager\introspector
	 */
	public $introspector;

	/**
	 * Storage of the Admin Interface
	 * 
	 * @var WpRest\Manager\Admin
	 */
	protected $admin;

	/**
	 * Construct the JSON API
	 *
	 * Good Practice: Call the Instance method to use the singleton
	 *
	 * @return void
	 */
	public function __construct() {
		$this->request = new Request(
			$_GET,
			$_POST,
			array(),
			$_COOKIE,
			$_FILES,
			$_SERVER
		);

		$this->introspector = new Introspector();
		$this->admin = new Admin();

		add_action('template_redirect', array(&$this, 'template_redirect'));
	}

	/**
	 * Return the Instance of the JSON API
	 * 
	 * @return object
	 */
	public static function Instance()
	{
		if (self::$instance == NULL)
			self::$instance = new Application();

		return self::$instance;
	}

	/**
	 * On template redirect
	 *
	 * Called on `template_redirect`
	 */
	public function template_redirect()
	{
		// Start the routing
		$this->router = new Router;

		// Not in the area, ignore.
		if (! $this->router->isApiRequest()) return;

		// Check to see if there's an appropriate API controller + method    
		$controller = $this->router->getController();

		if (! $this->isControllerActive($controller))
			return $this->applicationError(404);

		// Let's call the controller
		$controllers = $this->allControllers();

		// Controller doesn't exist anymore? Not found.
		if (! isset($controllers[$controller]))
			return $this->applicationError(404);

		$arguments = $this->router->getArguments();
		$method = $this->router->getMethod();
		
		$object = $controllers[$controller]['object'];

		if (! method_exists($object, $method)) :
			// Missing exception
			if (! method_exists($object, 'missingMethodException'))
				return $this->applicationError(404);

			$method = 'missingMethodException';
		endif;

		// Determine Authentication
		$Authentication = Authentication::Instance();
		
		if (! $Authentication->determineRequestAccess())
			return $this->applicationError(403);

		do_action('wp-rest-api-before-method-call', array($controller, $method, $arguments));

		// Hook onto this request
		do_action('wp-rest-api-controller-'.$controller, compact('method', 'arguments'));
		do_action('wp-rest-api-controller-'.$controller.'-method-'.$method, $arguments);

		// Do the response here
		$response = call_user_func_array(array($object, $method), $arguments);

		if (! ( $response instanceof \WpRest\Response ) AND !( $response instanceof \Symfony\Component\HttpFoundation ) )
			$response = \WpRest\Response::make($response);

		$response->send();

		do_action('wp-rest-api-after-method-call', array($controller, $method, $arguments));

		// Anddddddd we stop here!
		exit;
	}
	
	/**
	 * Determine the Controllers
	 *
	 * @return array
	 */
	public function allControllers()
	{
		$collection = new Collection;
		$controllers = (array) $collection->getControllers();

		if (count($controllers) == 0) return array();

		$index = array();
		foreach ($controllers as $c) :
			$name = $c->base;
			$index[$name] = array(
				'active' => (boolean) $this->isControllerActive($name),
				'object' => $c
			);
		endforeach;

		return $index;
	}

	/**
	 * Active Controller Index
	 *
	 * @return array
	 */
	public function activeControllers()
	{
		return (array) Settings::Instance()->active_controllers;
	}

	/**
	 * Determine if a controller is active
	 * 
	 * @param string
	 * @return boolean
	 */
	public function isControllerActive($name)
	{
		return (in_array($name, $this->activeControllers()));
	}

	/**
	 * Handle a 404 on the API
	 *
	 * @uses  do_action() Calls the `wp-rest-api-$code` hook to replace this
	 * @param  string
	 * @param  integer
	 */
	public function applicationError($statusCode, $message = null)
	{
		if ($message == NULL)
			$message = \Symfony\Component\HttpFoundation\Response::$statusTexts[$statusCode];

		$message = apply_filters('wp-rest-api-error-'.$statusCode, $message);
		
		if (has_action('wp-rest-api-'.$statusCode))
			return do_action('wp-rest-api-'.$statusCode, $message);

		\WpRest\Response::json(array(
			'status' => 'error',
			'message' => $message,
		), $statusCode)->send();
		exit;
	}
}