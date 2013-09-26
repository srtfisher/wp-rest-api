<?php
/*
Plugin Name: JSON API
Plugin URI: http://wordpress.org/extend/plugins/json-api/
Description: A RESTful API for WordPress
Version: 1.1
Author: Dan Phiffer
Author URI: http://phiffer.org/
*/

use JsonApi\Manager\Application;

$dir = json_api_dir();
if (! file_exists($dir.'/vendor/autoload.php')) :
	echo "Composer not setup for REST API";
	return;
else :
	require_once $dir.'/vendor/autoload.php';
endif;

/*
require_once $dir . '/singletons/api.php';
require_once $dir . '/singletons/query.php';
require_once $dir . '/singletons/introspector.php';
require_once $dir . '/singletons/response.php';
require_once $dir . '/models/post.php';
require_once $dir . '/models/comment.php';
require_once $dir . '/models/category.php';
require_once $dir . '/models/tag.php';
require_once $dir . '/models/author.php';
require_once $dir . '/models/attachment.php';
*/
/**
 * Initialize the Plugin
 * 
 * @return void
 */
function json_api_init() {
	global $json_api;

	if (phpversion() < 5.3)
		return add_action('admin_notices', 'json_api_php_version_warning');

	//if (! class_exists('Application'))
	//	return add_action('admin_notices', 'json_api_class_warning');

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
 * Warning when JSON API is setup incorrectly
 * 
 * @return void
 */
function json_api_class_warning() {
	echo "<div id=\"json-api-warning\" class=\"updated fade\"><p>Oops, JSON_API class not found. If you've defined a JSON_API_DIR constant, double check that the path is correct.</p></div>";
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
	$base = get_option('json_api_base', 'api');
	if (empty($base)) {
		return $wp_rules;
	}
	$json_api_rules = array(
		"$base\$" => 'index.php?json=info',
		"$base/(.+)\$" => 'index.php?json=$matches[1]'
	);
	return array_merge($json_api_rules, $wp_rules);
}

/**
 * Directory of the JSON API Plugin
 *
 * You can override the JSON API Directory by setting the
 * constant 'JSON_API_DIR'.
 * 
 * @return string
 */
function json_api_dir() {
	return (defined('JSON_API_DIR') && file_exists(JSON_API_DIR)) ? JSON_API_DIR : __DIR__;
}

// Add initialization and activation hooks
add_action('init', 'json_api_init');
register_activation_hook("$dir/json-api.php", 'json_api_activation');
register_deactivation_hook("$dir/json-api.php", 'json_api_deactivation');
