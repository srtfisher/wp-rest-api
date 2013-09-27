<?php namespace JsonApi\Manager;

use Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	JsonApi\Manager\Collection,
	JsonApi\Manager\Router;

/**
 * JSON API Manager
 *
 * @package  jsonapi
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
	 * Response Object
	 *
	 * @var Symfony\Component\HttpFoundation\Response
	 */
	public $response;

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

		$this->router = new Router;
		$this->introspector = new Introspector();
		$this->response = new Response();
		
		add_filter('query_vars', array(&$this, 'query_vars'));
		add_action('template_redirect', array(&$this, 'template_redirect'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		//add_action('update_option_json_api_base', array(&$this, 'flush_rewrite_rules'));
		//add_action('pre_update_option_json_api_controllers', array(&$this, 'update_controllers'));
	}

	public function query_vars($wp_vars) {
		$wp_vars[] = 'json';
		return $wp_vars;
	}

	/**
	 * Return an Instance of the JSON API
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
		// Not in the area, ignore.
		if (! $this->router->isApiRequest()) return;

		// Check to see if there's an appropriate API controller + method    
		$controller = $this->router->getController();
		
		if (! $this->isControllerActive($controller)) return;

		// Let's call the controller
		$controllers = $this->allControllers();

		// Controller doesn't exist anymore? Not found.
		if (! isset($controllers[$controller])) return;
		$arguments = $this->router->getArguments();
		$method = $this->router->getMethod();

		$object = $controllers[$controller]['object'];

		if (! method_exists($object, $method)) :
			// Missing exception
			if (! method_exists($object, 'missingMethodException'))
				return;

			$method = 'missingMethodException';
		endif;

		do_action('wp-rest-api-before-method-call', array($controller, $method, $arguments));

		// Do the response here
		
		do_action('wp-rest-api-after-method-call', array($controller, $method, $arguments));

		// Anddddddd we stop here!
		exit;
	}
	
	/**
	 * admin_menu hook
	 *
	 * Called on `admin_menu`
	 */
	public function admin_menu()
	{
		add_options_page('WP REST API Settings', 'REST API', 'manage_options', 'json-api', array(&$this, 'admin_options'));
	}
	
	/**
	 * Callback for Admin Page
	 * 
	 * @return void
	 * @access  private
	 */
	public function admin_options() {
		if (! current_user_can('manage_options'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		if (isset($_GET['_wpnonce']) AND isset($_GET['action']) AND isset($_GET['controller']) AND wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) :
			$controllers = $this->activeControllers();
			$settings = Settings::Instance();

			switch ($_GET['action'])
			{
				case 'activate' :
					if (! in_array($_GET['controller'], $controllers))
						$controllers[] = $_GET['controller'];

					$settings->active_controllers = $controllers;
					$settings->save();

					?><div class="updated"><p><?php _e('Controller activated.'); ?></p></div><?php
					break;

				case 'deactivate' :
					foreach ($controllers as $key => $name) :
						if ($name == $_GET['controller'])
							unset($controllers[$key]);
					endforeach;

					$settings->active_controllers = $controllers;
					$settings->save();

					?><div class="updated"><p><?php _e('Controller deactivated.'); ?></p></div><?php
					break;

			}
		elseif (isset($_POST['wp-rest-api-base']) AND wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) :
			$settings = Settings::Instance();
			$settings->base = sanitize_title_with_dashes($_POST['wp-rest-api-base']);
			$settings->save();
			$this->flush_rewrite_rules();

			?><div class="updated"><p><?php _e('API Base Updated.'); ?></p></div><?php
		endif;

		$available_controllers = $this->allControllers();
		?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>WP REST API Settings</h2>
	<form action="<?php echo admin_url('options-general.php?page=json-api'); ?>" method="post">
		<?php wp_nonce_field('update-options'); ?>
		<h3>Controllers</h3>

		<table id="all-plugins-table" class="widefat">
			<thead>
				<tr>
					<th class="manage-column check-column" scope="col"><input type="checkbox" /></th>
					<th class="manage-column" scope="col">Controller</th>
					<th class="manage-column" scope="col">Description</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column check-column" scope="col"><input type="checkbox" /></th>
					<th class="manage-column" scope="col">Controller</th>
					<th class="manage-column" scope="col">Description</th>
				</tr>
			</tfoot>
			<tbody class="plugins">
				<?php
				foreach ($available_controllers as $controllerName => $controller) {
					
					$error = false;
					$active = $controller['active'];
					$info = $controller['object']->controllerInfo();

					if (is_string($info)) {
						$active = false;
						$error = true;
						$info = array(
							'name' => $controller,
							'description' => "<p><strong>Error</strong>: $info</p>",
							'methods' => array(),
							'url' => null
						);
					}
					
					?>
					<tr class="<?php echo ($active ? 'active' : 'inactive'); ?>">
						<th class="check-column" scope="row">
							<input type="checkbox" name="controllers[]" value="<?php echo $controller; ?>" />
						</th>
						<td class="plugin-title">
							<strong><?php echo $info['name']; ?></strong>
							<div class="row-actions-visible">
								<?php
								
								if ($active) {
									echo '<a href="' . wp_nonce_url('options-general.php?page=json-api&amp;action=deactivate&amp;controller=' . $controllerName, 'update-options') . '" title="' . __('Deactivate this controller') . '" class="edit">' . __('Deactivate') . '</a>';
								} else if (!$error) {
									echo '<a href="' . wp_nonce_url('options-general.php?page=json-api&amp;action=activate&amp;controller=' . $controllerName, 'update-options') . '" title="' . __('Activate this controller') . '" class="edit">' . __('Activate') . '</a>';
								}
									
								if ($info['url']) {
									echo ' | ';
									echo '<a href="' . $info['url'] . '" target="_blank">Docs</a></div>';
								}
								
								?>
						</td>
						<td class="desc">
							<p><?php echo $info['description']; ?></p>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<h3>Address</h3>
		<p>Specify a base URL for JSON API. For example, using <code>api</code> as your API base URL would enable the following <code><?php bloginfo('url'); ?>/api/posts/</code>. If you assign a blank value, the API will only be available by setting a <code>json</code> query variable.</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">API base</th>
				<td><code><?php bloginfo('url'); ?>/</code><input type="text" name="wp-rest-api-base" value="<?php echo Settings::Instance()->base; ?>" size="15" /></td>
			</tr>
		</table>
		<?php if (!get_option('permalink_structure', '')) { ?>
			<br />
			<p><strong>Note:</strong> User-friendly permalinks are not currently enabled. <a target="_blank" class="button" href="options-permalink.php">Change Permalinks</a>
		<?php } ?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
<?php
	}
	
	public function get_nonce_id($controller, $method) {
		$controller = strtolower($controller);
		$method = strtolower($method);
		return "json_api-$controller-$method";
	}
	
	public function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	public function error($message = 'Unknown error', $status = 'error') {
		$this->response->respond(array(
			'error' => $message
		), $status);
	}
	
	public function include_value($key) {
		return $this->response->is_value_included($key);
	}
	
	/**
	 * Determine if the current request is in the API mode
	 * 
	 * @return boolean
	 */
	public function isApiRequest()
	{
		return $this->request->query->get('json');
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
}
