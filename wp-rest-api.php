<?php
/*
Plugin Name: WP REST API
Plugin URI: http://wordpress.org/extend/plugins/json-api/
Description: A RESTful API for WordPress
Version: 1.1
Author: Dan Phiffer
Author URI: http://phiffer.org/
*/

use WpRest\Manager\Application,
	WpRest\Manager\Settings;

if (! file_exists(__DIR__.'/vendor/autoload.php')) :
	echo "Composer not setup for REST API";
	return;
else :
	require_once __DIR__.'/vendor/autoload.php';
endif;

/**
 * Initialize the Plugin
 * 
 * @return void
 * @access private
 */
function json_api_init() {
	if (phpversion() < 5.3)
		return add_action('admin_notices', 'json_api_php_version_warning');
	
	Application::Instance();
}

/**
 * Shows a warning when PHP is out of date
 * 
 * @return void
 */
function json_api_php_version_warning() {
	echo "<div id=\"json-api-warning\" class=\"updated fade\"><p>Sorry, JSON API requires PHP version 5.0 or greater.</p></div>";
}

/**
 * Setup the Default Controllers
 *
 * @access  private
 */
function wp_rest_api_default_controllers($collection)
{
	$collection->register(new WpRest\Controller\Core);
	$collection->register(new WpRest\Controller\Posts);
}
add_action('wp-rest-api-controllers', 'wp_rest_api_default_controllers');

// Add initialization and activation hooks
add_action('init', 'json_api_init');
register_activation_hook("$dir/json-api.php", 'json_api_activation');
register_deactivation_hook("$dir/json-api.php", 'json_api_deactivation');