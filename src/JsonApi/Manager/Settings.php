<?php namespace JsonApi\Manager;

class Settings {
	/**
	 * Instance of this Class
	 * 
	 * @var JsonApi\Manager\Settings
	 */
	protected static $Instance;

	/**
	 * Key to save to
	 * @var string
	 */
	protected $key = 'wp_rest_options';

	/**
	 * Values to retrieve from
	 * 
	 * @var array
	 */
	protected $values = array();

	/**
	 * New Settings Object
	 */
	public function __construct()
	{
		$this->values = (array) get_option($this->key);
	}

	/**
	 * Return the Global Instance of Settings
	 *
	 * @return JsonApi\Manager\Settings
	 */
	public static function Instance()
	{
		if (self::$Instance) return self::$Instance;

		self::$Instance = new Settings;
		return self::$Instance;
	}

	/**
	 * Save to the Disk
	 * 
	 * @return mixed
	 */
	public function save()
	{
		return update_option($this->key, $this->values);
	}

	// =====================
	// Magic Methods
	// =====================
	public function __get($name)
	{
		return (isset($this->values[$name])) ? $this->values[$name] : null;
	}

	public function __set($name, $value)
	{
		return $this->values[$name] = $value;
	}

	public function __isset($name)
	{
		return (isset($this->values[$name]));
	}

	public function __unset($name)
	{
		unset($this->values[$name]);
	}
}