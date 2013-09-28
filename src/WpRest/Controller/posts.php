<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

class Posts extends BaseController implements ControllerInterface {
	public $base = 'posts';
	protected $type = 'post';

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
		$query = array();
		$a = \WpRest\Manager\Authentication::Instance();
		$introspector = Application::Instance()->introspector;

		$authed = $a->requestApiKey();
		$query = $this->request->query->all();
		$acceptableTerms = $introspector->acceptablePostSearchTerms();
		$wpQuery = array();

		if (count($query) > 0) : foreach ($query as $key => $value) :
			if (! in_array($key, $acceptableTerms))
				continue;

			if (($key == 'type' OR $key == 'status') AND ! $authed)
				continue;

			$wpQuery[$key] = $value;
		endforeach; endif;

		$wpQuery['type'] = $this->type;
		
		$posts = $introspector->get_posts($introspector->queryTranslate($wpQuery));

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
		if (! $post OR $post->post_type !== $this->type)
			return $this->error(404);

		return $this->response->json(array(
			'post' => new \WpRest\Model\Post($post)
		));
	}

	/**
	 * Create a new post
	 *
	 * POST /posts
	 */
	public function postIndex()
	{
		$post = new \WpRest\Model\Post();
		$_REQUEST['type'] = $this->type;
		$id = $post->create($_REQUEST);

		if (empty($id))
			return $this->error("Could not create post.");
		else
			return $this->response->json(array(
				'post' => $post
			));
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
