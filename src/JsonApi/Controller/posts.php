<?php namespace JsonApi\Controller;

class Posts extends BaseController implements ControllerInterface {
	public function getInfo()
	{
		return array(
			'name' => 'Posts',
			'description' => 'Data manipulation methods for posts'
		);
	}

	/**
	 * List all posts
	 * 
	 * GET /posts
	 */
	public function index()
	{
		global $json_api;
		$posts = $json_api->introspector->get_posts();
		return $this->posts_result($posts);
	}

	/**
	 * Store a new instance of a post
	 *
	 * POST /posts
	 */
	public function store() {
		global $json_api;
		if (!current_user_can('edit_posts')) {
			$json_api->error("You need to login with a user capable of creating posts.");
		}
		if (!$json_api->query->nonce) {
			$json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
		}
		$nonce_id = $json_api->get_nonce_id('posts', 'create_post');
		if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}
		nocache_headers();
		$post = new JSON_API_Post();
		$id = $post->create($_REQUEST);
		if (empty($id)) {
			$json_api->error("Could not create post.");
		}
		return array(
			'post' => $post
		);
	}
	
	/**
	 * Manage the results of this controller
	 * 
	 * @param mixed
	 * @param integer
	 * @return string
	 */
	public function response($data = array(), $http_code = 200)
	{
		global $wp_query;
		return parent::response(array(
			'count' => count($data),
			'count_total' => (int) $wp_query->found_posts,
			'pages' => $wp_query->max_num_pages,
			'posts' => $posts
		));
	}
}
