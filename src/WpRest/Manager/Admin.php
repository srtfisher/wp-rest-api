<?php namespace WpRest\Manager;

/**
 * Manage the Administration of the System
 *
 * @package  WpRest
 */
class Admin {
	public function __construct()
	{
		add_action('admin_menu', array(&$this, 'admin_menu'));
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
		$application = Application::Instance();

		if (! current_user_can('manage_options'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		if (isset($_GET['_wpnonce']) AND isset($_GET['action']) AND isset($_GET['controller']) AND wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) :
			$controllers = $application->activeControllers();
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
		elseif ( isset($_GET['action']) AND $_GET['action'] == 'newkey' AND wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) :
			$Authentication = Authentication::Instance();
			$access = $_GET['access'];

			if (! in_array($access, $Authentication->accessTypes()))
				wp_die('Unknown access key type.');

			$key = $Authentication->newKey($access);

			?><div class="updated"><p><?php echo sprintf('%s <code>%s</code> %s <code>%s</code> %s',
				__('Key'),
				$key,
				__('added with'),
				$access,
				__('access')
			); ?></p></div>
		<?php
		elseif (isset($_GET['action']) AND $_GET['action'] = 'delkey' AND isset($_GET['key']) AND wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) :
			Authentication::Instance()->deleteKey($_GET['key']);
			?>
			<div class="updated">
				<p>
					<?php _e('Key deleted.'); ?>
				</p>
			</div>
		<?php
		elseif (isset($_POST['wp-rest-api-base']) AND wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) :
			$settings = Settings::Instance();
			$settings->base = sanitize_title_with_dashes($_POST['wp-rest-api-base']);
			$settings->save();

			?><div class="updated"><p><?php _e('API Base Updated.'); ?></p></div><?php
		endif;

		$available_controllers = $application->allControllers();
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
					<th class="manage-column check-column" scope="col"></th>
					<th class="manage-column" scope="col">Controller</th>
					<th class="manage-column" scope="col">Description</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column check-column" scope="col"></th>
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
							'description' => sprintf('<p><strong>%s</strong>: %s</p>', __('Error'), $info),
							'methods' => array(),
							'url' => null
						);
					}
					?>
					<tr class="<?php echo ($active ? 'active' : 'inactive'); ?>">
						<th class="check-column" scope="row">
							
						</th>
						<td class="check-column plugin-title">
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

		<h2><?php _e('API Keys'); ?>
			<?php
			$auth = Authentication::Instance();
			foreach ($auth->accessTypes() as $accessLevel) :
				if ($accessLevel == Authentication::NONE) continue;
				?>
				<a href="<?php echo wp_nonce_url('options-general.php?page=json-api&action=newkey&access='.$accessLevel, 'update-options'); ?>" class="add-new-h2">
					<?php echo sprintf('%s %s %s', __('Add New'), $accessLevel, __('Key')); ?>
				</a>
			<?php endforeach; ?>
		</h2>

		<p><?php _e('Authentication with the WP REST API is done via API Keys. To perform a logged in request, append a API key to the request.'); ?></p>
		<table class="widefat">
			<?php foreach (array('thead', 'tfoot') as $t) : ?>
			<<?php echo $t; ?>>
				<tr>
					<th class="manage-column check-column" scope="col"></th>
					<th class="manage-column" scope="col">API Key</th>
					<th class="manage-column" scope="col">Access</th>
				</tr>
			</<?php echo $t; ?>>
			<?php endforeach; ?>

			<tbody>
				<?php
				$keys = Authentication::keys();
				if (count($keys) == 0) : ?>
				<tr>
					<th class="check-column" scope="row"></th>
					<td colspan="2"><p><?php _e('No API Keys found.'); ?></p></td>
				</tr>
				<?php else : foreach ($keys as $key => $keydata) : ?>
				<tr>
					<th class="check-column" scope="row"></th>
					<td>
						<p><code><?php echo $key; ?></code></p>
						<p><a href="<?php echo wp_nonce_url('options-general.php?page=json-api&action=delkey&key='.$key, 'update-options'); ?>"><?php _e('Delete Key'); ?></a></p>
					</td>
					<td><p><?php echo sprintf('%s: <code>%s</code>', __('Access'), __($keydata['access'])); ?></p></td>
				</tr>
				<?php endforeach; endif; ?>
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

		<?php if (! get_option('permalink_structure', '')) : ?>
			<br />
			<p><strong>Note:</strong> User-friendly permalinks are not currently enabled. <a target="_blank" class="button" href="options-permalink.php">Change Permalinks</a>
		<?php endif; ?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
<?php
	}
}