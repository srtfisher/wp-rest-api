<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application;

abstract class PostBase extends BaseController {
	protected $type = 'post';

	/**
	 * List all posts
	 * 
	 * GET /{type}
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
	 * GET /{type}/{id}
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
	 * Delete a Post
	 *
	 * DELETE /{type}/{id}
	 */
	public function deleteSingle($id = 0)
	{
		$id = (int) $id;
		if ($id < 1) return $this->error(404);

		wp_delete_post($id);
		return $this->response->json(array(
			'status' => 'deleted',
		));
	}

	/**
	 * Create a new post
	 *
	 * POST /{type}
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
			'current_page' => (($this->request->query->has('page')) ? (int) $this->request->query->get('page') : 1),
			
			$this->type.'s' => $data,
		), $http_code, $headers);
	}
}
