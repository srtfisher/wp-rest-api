<?php namespace WpRest\Manager;

use WpRest\Exception\AuthenticationException;

/**
 * Authentication
 *
 * Manages Authentication for the Application
 *
 * @package  wprest
 * @subpackage Authentication
 */
class Authentication {
	/**
	 * Constant to define a permission for read and writing
	 *
	 * @var  string
	 */
	const READ_WRITE = 'read-write';

	/**
	 * Define a permission for only reading
	 *
	 * @var  string
	 */
	const READ = 'read';

	/**
	 * Static Instance Store
	 * 
	 * @var object
	 */
	protected static $instance;
	
	/**
	 * Current acccess
	 *
	 * @var  object
	 */
	protected $access;

	/**
	 * Return the Instance of the JSON API
	 * 
	 * @return object
	 */
	public static function Instance()
	{
		if (self::$instance == NULL)
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Retrieve all API Keys
	 *
	 * @return  array
	 */
	public function keys()
	{
		$Settings = Settings::Instance();

		return (isset($Settings->keys)) ? (array) $Settings->keys : array();
	}

	/**
	 * Register a new key
	 *
	 * @return  string
	 */
	public function newKey($access = self::READ)
	{
		if (! in_array($access, $this->accessTypes() ))
			throw new AuthenticationException(sprintf('Unknown access type passed: '.$access));

		$keys = $this->keys();

		// Loop to find a unique key
		while(! $found) {
			$key = uniqid('', true);

			if (! isset($keys[$key]))
				$found = true;
		}

		$keys[$key] = array(
			'time' => current_time('timestamp'),
			'access' => $access
		);

		$Settings = Settings::Instance();
		$Settings->keys = $keys;
		$Settings->save();

		return $key;
	}

	/**
	 * Valid Access Types
	 *
	 * @uses do_action() Calls `wp-rest-api-auth-types`
	 * @return array
	 */
	public function accessTypes()
	{
		return (array) apply_filters('wp-rest-api-auth-types', array(
			self::READ,
			self::READ_WRITE
		));
	}

	/**
	 * Determine the Current Access from the Application Request
	 *
	 * @return  void
	 */
	public function determineAccess()
	{
		if ($this->access) return $this->access;

		$a = Application::Instance();
		$key = $a->request->get('apiKey');

		if (! $key) return false;
		$keys = $this->keys();

		// Unknown API Key
		if (! isset($keys[$key]))
			return $a->applicationError(403, 'Unauthorized API Key.');

		$this->access = $keys[$key];
		return $this->access;
	}

	/**
	 * Determine if the current request has valid permission
	 *
	 * Override this access request by checking out the
	 * `'wp-rest-api-auth-check-$httpMethod` hook.
	 * 
	 * @return  boolean
	 */
	public function determineRequestAccess()
	{
		$httpMethod = Application::Instance()->request->server->get('REQUEST_METHOD');
		$access = $this->determineAccess();
		
		if (! $access) :
			// Un-authed request
			$level = self::READ;
		else :
			$level = $access['access'];
		endif;

		if (has_action('wp-rest-api-auth-check-'.$httpMethod))
			return do_action('wp-rest-api-auth-check-'.$httpMethod, $access);

		switch (strtolower($httpMethod))
		{
			case 'get' :
				return ($level == self::READ OR $level == self::READ_WRITE);
				break;

			case 'post' :
			case 'put' :
			case 'delete' :
			default :
				return ($level == self::READ_WRITE);
				break;
		}
	}
}