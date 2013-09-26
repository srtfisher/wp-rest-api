<?php
/*
Plugin Name: JSON API
Plugin URI: http://wordpress.org/extend/plugins/json-api/
Description: A RESTful API for WordPress
Version: 1.1
Author: Dan Phiffer
Author URI: http://phiffer.org/
*/

use JsonApi\Manager\Application,
	JsonApi\Manager\Settings;

if (! file_exists($dir.'/vendor/autoload.php')) :
	echo "Composer not setup for REST API";
	return;
else :
	require_once $dir.'/vendor/autoload.php';
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

	add_filter('rewrite_rules_array', 'json_api_rewrites');

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
 * Activation Hook
 *
 * @access private
 * @return void
 */
function json_api_activation() {
	// Add the rewrite rule on activation
	global $wp_rewrite;
	add_filter('rewrite_rules_array', 'json_api_rewrites');
	$wp_rewrite->flush_rules();
}

/**
 * Deactivation Hook
 *
 * @access private
 * @return void
 */
function json_api_deactivation() {
	// Remove the rewrite rule on deactivation
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

/**
 * Filter Applied for Rewrite Actions
 *
 * @access private
 */
function json_api_rewrites($wp_rules) {
	$settings = Settings::Instance();

	if (! $settings->base)
		$base = 'api';
	else
		$base = $settings->base;

	if (empty($base)) {
		return $wp_rules;
	}
	$json_api_rules = array(
		"$base\$" => 'index.php?apiRequest=Coreinfo',
		"$base/(.+)\$" => 'index.php?apiRequest=$matches[1]'
	);

	return array_merge($json_api_rules, $wp_rules);
}


/**
 * Setup the Default Controllers
 *
 * @access  private
 */
function wp_rest_api_default_controllers($collection)
{
	$collection->register(new JsonApi\Controller\Core);
	$collection->register(new JsonApi\Controller\Posts);
}
add_action('wp-rest-api-controllers', 'wp_rest_api_default_controllers');

// Add initialization and activation hooks
add_action('init', 'json_api_init');
register_activation_hook("$dir/json-api.php", 'json_api_activation');
register_deactivation_hook("$dir/json-api.php", 'json_api_deactivation');
