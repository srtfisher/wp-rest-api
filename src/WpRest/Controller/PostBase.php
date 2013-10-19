<?php namespace WpRest\Controller;

use WpRest\Response,
	WpRest\Manager\Application,
	WpRest\Common\Helper;

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

		$query = $this->request->query;
		$wpQuery = array();

		// Check by ID
		if ($query->has('id') OR $query->has('post_id')) :
			$wpQuery['p'] = ($query->has('id')) ? (int) $query->get('id') : (int) $query->get('post_id');

		// Check for post slug
		elseif ( $query->has('slug') OR $query->has('post_slug') ) :
			$wpQuery['name'] = ($query->has('slug')) ? $query->get('slug') : $query->get('post_slug');
		endif;
		$wpQuery['post_type'] = $this->type;
		$posts = $introspector->get_posts($wpQuery);
		
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
	 * Update a Post
	 *
	 * POST /{type}/{id}
	 */
	public function postSingle($id = 0)
	{
		$id = (int) $id;
		if ($id < 1) return $this->error(404);

		$post = get_post($id);
		if (! $post) return $this->error(404);

		$object = new \WpRest\Model\Post($post);
		$object->update($_REQUEST);

		return $this->response->json(array(
			'post' => $object
		));
	}

	/**
	 * Create a new post
	 *
	 * PUT /{type}
	 */
	public function putIndex()
	{
		$post = new \WpRest\Model\Post();

		$parameters = $this->request->request;
		
		// We override the request here to manually specify the post_type
		// specific to this controller
		$parameters->set('type', $this->type);
		$id = $post->create($parameters->all());

		if (empty($id))
			return $this->error('Could not create post.');
		else
			return $this->response->json(array(
				'post' => $post
			));
	}

	/**
	 * GET /{type}/search
	 */
	public function getSearch()
	{
		global $json_api;
		$url = parse_url($this->request->server->get('REQUEST_URI'));

		$query = $this->request->query->all();
		array_shift($query);

		$query['post_type'] = $this->type;
		$defaults = array(
			'ignore_sticky_posts' => true
		);
		unset($query['json']);
		unset($query['post_status']);
		unset($query['post_type']);
		
		$query = array_merge($defaults, $query);
		$posts = $this->introspector->get_posts($query);
		$result = $this->response($posts, 200, array(), array('query' => $query));

		return $result;
	}

	/**
	 * Delete a Meta Record
	 *
	 * Required Arguments:
	 * - key
	 * - value (optional)
	 * 
	 * Access via DELETE /{type}/meta/{id}
	 */
	public function deleteMeta($id = 0)
	{
		$id = (int) $id;
		if ($id < 1) return $this->error(404);

		$post = get_post($id);
		if (! $post) return $this->error(404);

		$key = $this->request->get('key');
		$value = $this->request->get('value', '');
		
		return $this->response->json(array(
			'status' => delete_post_meta($id, $key, $value)
		));
	}

	/**
	 * Save a Meta Record for a Post
	 *
	 * Required Arguments:
	 * - key
	 * - value (serialized already)
	 * - previous_value (mixed)
	 * 
	 * Access via PUT /{type}/meta/{post id}/{key?}
	 */
	public function putMeta($id = 0, $key = '')
	{
		$id = (int) $id;
		if ($id < 1) return $this->error(404);

		$post = get_post($id);
		if (! $post) return $this->error(404);

		$key = ($key == '') ? $this->request->get('key', '') : $key;
		$value = $this->request->get('value', '');
		$previous = $this->request->get('previous_value', '');

		return $this->response->json(array(
			'status' => update_post_meta($id, $key, $value, $previous)
		));
	}

	/**
	 * Access a List of Meta Fields for a Post
	 *
	 * GET {type}/{meta}/{post id}/{key?}
	 */
	public function getMeta($id = 0, $key = '')
	{
		$id = (int) $id;
		$key = trim($key);
		if ($id < 1) return $this->error(404);

		$post = get_post($id);
		if (! $post OR $post->post_type !== $this->type) return $this->error(404);

		$single = $this->request->get('single', true);
		$single = (strtolower($single) == 'true' OR $single == true);

		if (empty($key)) :
			// We're getting all of the meta values
			$meta = get_metadata('post', $id);
			$index = array();

			if ($meta) :
				foreach ($meta as $k => $metaIndex)
				{
					$index[$k] = array();

					if ($metaIndex) : foreach ($metaIndex as $v)
						$index[$k][] = Helper::jsonDecode($v);
					endif;
				}
			endif;

			return $this->response->json(array(
				'response' => $index,
			));
		else :
			return $this->response->json(array(
				'key' => $key,
				'response' => Helper::jsonDecode(get_post_meta($id, $key, $single))
			));
		endif;
	}
	
	/**
	 * Manage the results of this controller
	 * 
	 * @param mixed
	 * @param integer
	 * @param  array Additional entries to include in the response
	 * @return string
	 */
	public function response($data = array(), $http_code = 200, array $headers = array(), array $addon = array())
	{
		global $wp_query;
		$response = array(
			'count' => count($data),
			'count_total' => (int) $wp_query->found_posts,
			'pages' => $wp_query->max_num_pages,
			'current_page' => (($this->request->query->has('page')) ? (int) $this->request->query->get('page') : 1),
			
			$this->type.'s' => $data,
		);
		$response = array_merge($response, $addon);

		return $this->response->json($response, $http_code, $headers);
	}
}
