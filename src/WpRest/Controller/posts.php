<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

class Posts extends BaseController implements ControllerInterface {
	public $base = 'posts';
	
	public function controllerInfo()
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
	public function getIndex()
	{
		global $wp_query;
		$introspector = Application::Instance()->introspector;
		$posts = $introspector->get_posts();

		return $this->response($posts);
	}

	/**
	 * GET /posts/{id}
	 */
	public function getSingle($id = 0)
	{
		$id = (int) $id;
		
		if ($id < 1) :
			// Find the post by another variable
					
		endif;

		$post = get_post($id);
		if (! $post)
			return $this->error(404);

		return $this->response->json(array(
			'post' => new \WpRest\Model\Post($post)
		));
	}

	/**
	 * Search Posts
	 *
	 * GET /posts/search
	 */
	public function getSearch()
	{
		if (! $this->request->get('s'))
			return $this->error(400, 'No search variable passed.');

		$introspector = Application::Instance()->introspector;

		$posts = $introspector->get_posts(array(
			's' => $this->request->get('s')
		));
		
		return $this->response($posts);
	}

	/**
	 * Create a new post
	 *
	 * POST /posts
	 */
	public function postIndex()
	{
		$post = new \WpRest\Model\Post();
		$id = $post->create($_REQUEST);

		if (empty($id))
			return $this->error("Could not create post.");
		else
			return $this->response->json(array(
				'post' => $post
			));
	}

	/**
	 * Category listing
	 * 
	 * GET /posts/categories
	 */
	public function getCategories()
	{
		$introspector = Application::Instance()->introspector;
		$categories = $introspector->get_categories();

		return $this->response->json(array(
			'count' => count($categories),
			'categories' => $categories
		));
	}

	/**
	 * Create category
	 *
	 * POST /post/tags
	 */
	public function postCategories()
	{

	}

	/**
	 * GET /posts/tags
	 */
	public function getTags() {
		$tags = $this->introspector->get_tags();
		return $this->response->json(array(
			'count' => count($tags),
			'tags' => $tags
		));
	}

	/**
	 * Create new tag
	 * 
	 * POST /posts/tags
	 */
	public function postTags()
	{

	}
	
	/**
	 * Manage the results of this controller
	 * 
	 * @param mixed
	 * @param integer
	 * @return string
	 */
	public function response($data = array(), $http_code = 200, array $headers = array())
	{
		global $wp_query;

		return $this->response->json(array(
			'count' => count($data),
			'count_total' => (int) $wp_query->found_posts,
			'pages' => $wp_query->max_num_pages,
			'posts' => $data
		), $http_code, $headers);
	}
}
