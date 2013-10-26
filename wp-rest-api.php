<?php
/*
Plugin Name: WP REST API
Plugin URI: http://wordpress.org/extend/plugins/json-api/
Description: A RESTful API for WordPress
Version: 1.0
Author: Sean Fisher
Author URI: http://seanfisher.co/
*/

use WpRest\Manager\Application;

if (! file_exists(__DIR__.'/vendor/autoload.php'))
	wp_die('Composer not setup for REST API');
else
	require_once __DIR__.'/vendor/autoload.php';

/**
 * Initialize the Plugin
 * 
 * @return void
 * @access private
 */
function json_api_init() {
	if (phpversion() < 5.3)
		return add_action('admin_notices', 'json_api_php_version_warning');
	
	// Call to setup the application
	Application::Instance();
}
add_action('init', 'json_api_init');

/**
 * Shows a warning when PHP is out of date
 * 
 * @return void
 */
function json_api_php_version_warning() {
	echo "<div id=\"json-api-warning\" class=\"updated fade\"><p>Sorry, JSON API requires PHP version 5.3 or greater.</p></div>";
}

/**
 * Setup the Default Controllers
 *
 * @access  private
 */
function wp_rest_api_default_controllers($collection)
{
	$collection->register(new WpRest\Controller\Core);
	$collection->register(new WpRest\Controller\Post);
	$collection->register(new WpRest\Controller\Page);
	$collection->register(new WpRest\Controller\Category);
	$collection->register(new WpRest\Controller\Tag);
}
add_action('wp-rest-api-controllers', 'wp_rest_api_default_controllers');