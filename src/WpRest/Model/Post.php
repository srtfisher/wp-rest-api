<?php namespace WpRest\Model;

class Post
{
	public $id;              // Integer
	public $type;            // String
	public $slug;            // String
	public $url;             // String
	public $status;          // String ("draft", "published", or "pending")
	public $title;           // String
	public $title_plain;     // String
	public $content;         // String (modified by read_more query var)
	public $excerpt;         // String
	public $date;            // String (modified by date_format query var)
	public $modified;        // String (modified by date_format query var)
	public $categories;      // Array of objects
	public $tags;            // Array of objects
	public $author;          // Object
	public $comments;        // Array of objects
	public $attachments;     // Array of objects
	public $comment_count;   // Integer
	public $comment_status;  // String ("open" or "closed")
	public $thumbnail;       // String
	public $custom_fields;   // Object (included by using custom_fields query var)
	
	protected $introspector;

	public function __construct($wp_post = null)
	{
		$this->introspector = \WpRest\Manager\Application::Instance()->introspector;

		if (! empty($wp_post)) {
			$this->import_wp_object($wp_post);
		}
	}

	/**
	 * Create or update the post depending upon if the ID is passed
	 *
	 * @param  array Parameters
	 * @throws  WpRest\Exception\DataException
	 */
	public static function createOrUpdate($values = array())
	{
		$values = (array) $values;
		if (! $values)
			throw new \WpRest\Exception\DataException('No parameters passed to create/update post.');

		$object = new Post;

		if (isset($values['id']) AND $values['id'] > 0) :
			// Updating
			$object->id = $values['id'];
			return $object->update($values);
		else :
			// Creating
			return $object->create($values);
		endif;
	}
	
	public function create($values = null)
	{
		unset($values['id']);
		if (empty($values) || empty($values['title'])) {
			$values = array(
				'title' => 'Untitled',
				'content' => ''
			);
		}
		return $this->save($values);
	}
	
	public function update($values)
	{
		$values['id'] = $this->id;
		return $this->save($values);
	}
	
	public function save($values = null)
	{
		global $user_ID;
		
		$wp_values = array();
		
		if (! empty($values['id'])) 
			$wp_values['ID'] = $values['id'];
		
		
		if (! empty($values['type'])) 
			$wp_values['post_type'] = $values['type'];
		
		
		if (! empty($values['status'])) 
			$wp_values['post_status'] = $values['status'];
		
		
		if (! empty($values['title'])) 
			$wp_values['post_title'] = $values['title'];
		
		
		if (! empty($values['content'])) 
			$wp_values['post_content'] = $values['content'];
		
		
		if (! empty($values['author'])) {
			$author = $introspector->get_author_by_login($values['author']);
			$wp_values['post_author'] = $author->id;
		}
		
		if (isset($values['categories'])) {
			$categories = explode(',', $values['categories']);
			foreach ($categories as $category_slug) {
				$category_slug = trim($category_slug);
				$category = $this->introspector->get_category_by_slug($category_slug);
				if (empty($wp_values['post_category'])) {
					$wp_values['post_category'] = array($category->id);
				} else {
					array_push($wp_values['post_category'], $category->id);
				}
			}
		}
		
		if (isset($values['tags'])) {
			$tags = explode(',', $values['tags']);
			foreach ($tags as $tag_slug) {
				$tag_slug = trim($tag_slug);
				if (empty($wp_values['tags_input'])) {
					$wp_values['tags_input'] = array($tag_slug);
				} else {
					array_push($wp_values['tags_input'], $tag_slug);
				}
			}
		}
		
		if (isset($wp_values['ID']))
			$this->id = wp_update_post($wp_values);
		else
			$this->id = wp_insert_post($wp_values);
		
		if (! empty($_FILES['attachment'])) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
			include_once ABSPATH . '/wp-admin/includes/media.php';
			include_once ABSPATH . '/wp-admin/includes/image.php';
			$attachment_id = media_handle_upload('attachment', $this->id);
			$this->attachments[] = new Attachment($attachment_id);
			unset($_FILES['attachment']);
		}
		
		$wp_post = get_post($this->id);
		$this->import_wp_object($wp_post);
		
		return $this->id;
	}
	
	/**
	 * Import from WP_Post
	 * 
	 * @param WP_Post
	 */
	protected function import_wp_object($wp_post)
	{
		$date_format = $json_api->query->date_format;
		$this->id = (int) $wp_post->ID;
		setup_postdata($wp_post);
		$this->set_value('type', $wp_post->post_type);
		$this->set_value('slug', $wp_post->post_name);
		$this->set_value('url', get_permalink($this->id));
		$this->set_value('status', $wp_post->post_status);
		$this->set_value('title', get_the_title($this->id));
		$this->set_value('title_plain', strip_tags(@$this->title));
		$this->set_content_value();
		$this->set_value('excerpt', apply_filters( 'get_the_excerpt', $wp_post->post_excerpt ));
		$this->set_value('date', get_the_time($date_format));
		$this->set_value('modified', date($date_format, strtotime($wp_post->post_modified)));
		$this->set_categories_value();
		$this->set_tags_value();
		$this->set_author_value($wp_post->post_author);
		$this->set_comments_value();
		$this->set_attachments_value();
		$this->set_value('comment_count', (int) $wp_post->comment_count);
		$this->set_value('comment_status', $wp_post->comment_status);
		$this->set_thumbnail_value();
		$this->set_custom_fields_value();
	}
	
	public function set_value($key, $value) {
		global $json_api;
		if ($this->include_value($key)) {
			$this->$key = $value;
		} else {
			unset($this->$key);
		}
	}
		
	function set_content_value() {
		global $json_api;
		if ($this->include_value('content')) {
			$content = get_the_content($json_api->query->read_more);
			$content = apply_filters('the_content', $content);
			$content = str_replace(']]>', ']]&gt;', $content);
			$this->content = $content;
		} else {
			unset($this->content);
		}
	}
	
	function set_categories_value() {
		global $json_api;
		if ($this->include_value('categories')) {
			$this->categories = array();
			if ($wp_categories = get_the_category($this->id)) {
				foreach ($wp_categories as $wp_category) {
					$category = new Category($wp_category);
					if ($category->id == 1 && $category->slug == 'uncategorized') {
						// Skip the 'uncategorized' category
						continue;
					}
					$this->categories[] = $category;
				}
			}
		} else {
			unset($this->categories);
		}
	}
	
	function set_tags_value() {
		global $json_api;
		if ($this->include_value('tags')) {
			$this->tags = array();
			if ($wp_tags = get_the_tags($this->id)) {
				foreach ($wp_tags as $wp_tag) {
					$this->tags[] = new Tag($wp_tag);
				}
			}
		} else {
			unset($this->tags);
		}
	}
	
	function set_author_value($author_id) {
		global $json_api;
		if ($this->include_value('author')) {
			$this->author = new Author($author_id);
		} else {
			unset($this->author);
		}
	}
	
	function set_comments_value() {
		global $json_api;
		if ($this->include_value('comments'))
			$this->comments = $this->introspector->get_comments($this->id);
		else
			unset($this->comments);
	}
	
	function set_attachments_value() {
		global $json_api;
		if ($this->include_value('attachments')) {
			$this->attachments = $this->introspector->get_attachments($this->id);
		} else {
			unset($this->attachments);
		}
	}
	
	function set_thumbnail_value() {
		global $json_api;
		if (!$this->include_value('thumbnail') ||
				!function_exists('get_post_thumbnail_id')) {
			unset($this->thumbnail);
			return;
		}
		$attachment_id = get_post_thumbnail_id($this->id);
		if (!$attachment_id) {
			unset($this->thumbnail);
			return;
		}
		$thumbnail_size = $this->get_thumbnail_size();
		list($thumbnail) = wp_get_attachment_image_src($attachment_id, $thumbnail_size);
		$this->thumbnail = $thumbnail;
	}
	
	function set_custom_fields_value() {
		global $json_api;
		if ($this->include_value('custom_fields') &&
				$json_api->query->custom_fields) {
			$keys = explode(',', $json_api->query->custom_fields);
			$wp_custom_fields = get_post_custom($this->id);
			$this->custom_fields = new stdClass();
			foreach ($keys as $key) {
				if (isset($wp_custom_fields[$key])) {
					$this->custom_fields->$key = $wp_custom_fields[$key];
				}
			}
		} else {
			unset($this->custom_fields);
		}
	}
	
	function get_thumbnail_size() {
		global $json_api;
		if ($json_api->query->thumbnail_size) {
			return $json_api->query->thumbnail_size;
		} else if (function_exists('get_intermediate_image_sizes')) {
			$sizes = get_intermediate_image_sizes();
			if (in_array('post-thumbnail', $sizes)) {
				return 'post-thumbnail';
			}
		}
		return 'thumbnail';
	}
	
	public function include_value($key)
	{
		return true;
	}
}
