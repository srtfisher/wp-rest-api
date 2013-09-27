<?php namespace WpRest\Manager;

use WpRest\Controller\ControllerInterface;

/**
 * Manager the controllers and store them all
 *
 * To register a controller, look at the `wp-rest-api-controllers`
 * hook. It will be called with an argument of the `Collection` class.
 *
 * Register it via the `register` method.
 */
class Collection {
	protected $controllers;

	/**
	 * Store Controllers
	 * 
	 * @param array
	 */
	public function __construct($controllers = array())
	{
		$this->controllers = $controllers;

		do_action('wp-rest-api-controllers', $this);
	}

	/**
	 * Register a controller
	 * 
	 * @param object Controller Object: Intance of ControllerInterface
	 */
	public function register($controller)
	{
		if (! ( $controller instanceof ControllerInterface ))
			wp_die(sprintf('Controller %s is not an instance of ControllerInterface', $controller->base));

		$this->controllers[$controller->base] = $controller;
	}

	/**
	 * Deregister a Controller
	 *
	 * @param  string
	 */
	public function deregister($name)
	{
		unset($this->controllers[$name]);
	}

	/**
	 * Retrieve all controllers
	 * 
	 * @return array
	 */
	public function getControllers()
	{
		return $this->controllers;
	}
}