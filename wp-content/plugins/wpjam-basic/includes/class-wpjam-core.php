<?php
add_action('init',				['WPJAM_Core', 'register_post_types'], 11);	// 注册自定义日志类型
add_action('init',				['WPJAM_Core', 'register_taxonomies'], 11);	// 注册自定义分类

add_filter('post_type_link',	['WPJAM_Core', 'post_type_link'], 1, 2);	// 设置自定义日志的链接

class WPJAM_Core{
	protected static $post_types		= [];
	protected static $taxonomies		= [];
	protected static $post_options		= [];
	protected static $term_options		= [];
	protected static $option_settings	= [];
	protected static $apis				= [];
	protected static $menu_pages			= [];

	public static function register_post_type($post_type, $args=[]){
		self::$post_types[$post_type]	= $args;
	}

	public static function register_post_types(){
		$post_types	= apply_filters('wpjam_post_types', self::$post_types);

		if(!$post_types) {
			return;
		}

		foreach ($post_types as $post_type => $post_type_args) {
			$post_type_args	= wp_parse_args($post_type_args, [
				'public'			=> true,
				'show_ui'			=> true,
				'hierarchical'		=> false,
				'rewrite'			=> true,
				'permastruct'		=> false,
				'thumbnail_size'	=> '',
				// 'capability_type'	=> $post_type,
				// 'map_meta_cap'		=> true,
				'supports'			=> ['title'],
				'taxonomies'		=> [],
			]);

			if(empty($post_type_args['taxonomies'])){
				unset($post_type_args['taxonomies']);
			}

			if($post_type_args['hierarchical']){
				$post_type_args['supports'][]	= 'page-attributes';
			}

			$permastruct = $post_type_args['permastruct'];

			if(empty($post_type_args['rewrite'])){
				$post_type_args['rewrite']	= $permastruct ? true : false;
			}

			if($post_type_args['rewrite']){
				if(is_array($post_type_args['rewrite'])){
					$post_type_args['rewrite']	= wp_parse_args($post_type_args['rewrite'], ['with_front'=>false, 'feeds'=>false]);
				}else{
					$post_type_args['rewrite']	= ['with_front'=>false, 'feeds'=>false];
				}
			}

			register_post_type($post_type, $post_type_args);

			if($permastruct){
				if(strpos($permastruct, "%post_id%") || strpos($permastruct, "%{$post_type}_id%")){
					$permastruct_args			= get_post_type_object($post_type)->rewrite;
					$permastruct_args['feed']	= $permastruct_args['feeds'];

					$permastruct	= str_replace('%post_id%', '%'.$post_type.'_id%', $permastruct); 

					add_rewrite_tag('%'.$post_type.'_id%', '([0-9]+)', "post_type=$post_type&p=" );

					add_permastruct($post_type, $permastruct, $permastruct_args);
				}
			}
		}
	}

	public static function get_post_types(){
		return self::$post_types;
	}

	// 设置自定义日志的链接
	public static function post_type_link($post_link, $post){
		$post_type	= $post->post_type;

		if(empty(get_post_type_object($post_type)->permastruct)){
			return $post_link;
		}

		$post_link	= str_replace( '%'.$post_type.'_id%', $post->ID, $post_link );

		if(strpos($post_link, '%') === false){
			return $post_link;
		}

		$taxonomies = get_taxonomies(['object_type'=>[$post_type]], 'objects');

		if(!$taxonomies){
			return $post_link;
		}

		foreach ($taxonomies as $taxonomy=>$taxonomy_object) {
			if($taxonomy_rewrite = $taxonomy_object->rewrite){

				if(strpos($post_link, '%'.$taxonomy_rewrite['slug'].'%') === false){
					continue;
				}

				if($terms = get_the_terms($post->ID, $taxonomy)){
					$post_link	= str_replace( '%'.$taxonomy_rewrite['slug'].'%', current($terms)->slug, $post_link );
				}else{
					$post_link	= str_replace( '%'.$taxonomy_rewrite['slug'].'%', $taxonomy, $post_link );
				}
			}
		}

		return $post_link;
	}

	public static function register_taxonomy($taxonomy, $args=[]){
		self::$taxonomies[$taxonomy]	= ['object_type'=>$args['object_type'], 'args'=>$args];
	}

	public static function register_taxonomies(){
		$taxonomies	= apply_filters('wpjam_taxonomies', self::$taxonomies);

		if($taxonomies) {
			foreach ($taxonomies as $taxonomy=>$taxonomy_args) {
				$object_type	= $taxonomy_args['object_type'];
				$taxonomy_args	= wp_parse_args($taxonomy_args['args'], [
					'show_ui'			=> true,
					'show_in_nav_menus'	=> false,
					'show_admin_column'	=> true,
					'hierarchical'		=> true,
				]);

				register_taxonomy($taxonomy, $object_type, $taxonomy_args);
			}
		}	
	}

	public static function get_taxonomies(){
		return self::$taxonomies;
	}

	public static function register_post_option($meta_box, $args=[]){
		self::$post_options[$meta_box]	= $args;
	}

	public static function get_post_options($post_type){
		$post_type_options	= [];
		$post_options		= apply_filters('wpjam_post_options', self::$post_options, $post_type);
		
		if($post_options){
			foreach($post_options as $meta_key => $post_option){
				$post_option = wp_parse_args($post_option, [
					'post_types'	=> 'all',
					'post_type'		=> ''
				]);

				if($post_option['post_type'] && $post_option['post_types'] == 'all'){
					$post_option['post_types'] = [$post_option['post_type']];
				}

				if($post_option['post_types'] == 'all' || in_array($post_type, $post_option['post_types'])){
					$post_type_options[$meta_key] = $post_option;
				}
			}
		}

		return apply_filters('wpjam_'.$post_type.'_post_options', $post_type_options);
	}

	public static function get_post_fields($post_type){
		if($post_options = self::get_post_options($post_type)) {
			return call_user_func_array('array_merge', array_column(array_values($post_options), 'fields'));
		}else{
			return [];
		}
	}

	public static function register_term_option($key, $args=[]){
		self::$term_options[$key]	= $args;
	}

	public static function get_term_options($taxonomy){
		$taxonomy_options	= [];

		$term_options		= apply_filters('wpjam_term_options', self::$term_options, $taxonomy);

		if($term_options){
			foreach ($term_options as $key => $term_option) {
				$term_option	= wp_parse_args( $term_option, [
					'taxonomies'	=> 'all',
					'taxonomy'		=> ''
				]);

				if($term_option['taxonomy'] && $term_option['taxonomies'] == 'all'){
					$term_option['taxonomies'] = [$term_option['taxonomy']];
				}

				if($term_option['taxonomies'] == 'all' || in_array($taxonomy, $term_option['taxonomies'])){
					$taxonomy_options[$key]	= $term_option;
				}
			}
		}

		return apply_filters('wpjam_'.$taxonomy.'_term_options', $taxonomy_options);
	}

	public static function register_option($option_name, $args=[]){
		self::$option_settings[$option_name]	= $args;
	}

	public static function get_options(){
		return self::$option_settings;
	}

	public static function get_option($option_name){
		$option_setting	= apply_filters(self::get_filter_name($option_name,'setting'), []);

		if(!$option_setting){

			if(self::$option_settings && !empty(self::$option_settings[$option_name])){
				$option_setting		= self::$option_settings[$option_name];
			}else{
				$option_settings	= apply_filters('wpjam_settings', [], $option_name);

				if(!$option_settings || empty($option_settings[$option_name])) {
					return false;
				}

				$option_setting	= $option_settings[$option_name];
			}	
		}

		if(empty($option_setting['sections'])){	// 支持简写
			if(isset($option_setting['fields'])){
				$fields		= $option_setting['fields'];
				unset($option_setting['fields']);
				$option_setting['sections']	= [$option_name => compact('fields')];
			}else{
				$option_setting['sections']	= $option_setting;
			}
		}

		return wp_parse_args($option_setting, [
			'option_group'	=> $option_name, 
			'option_page'	=> $option_name, 
			'ajax'			=> true,
			'option_type'	=> 'array', 	// array：设置页面所有的选项作为一个数组存到 options 表， single：每个选项单独存到 options 表。
			'capability'	=> 'manage_options',
			'sections'		=> []
		]);
	}

	public static function register_api($json, $args){
		self::$apis[$json]	= $args;
	}

	public static function get_apis(){
		return self::$apis;
	}

	public static function get_api($json){
		if(self::$apis && !empty(self::$apis[$json])){
			return self::$apis[$json];
		}else{
			return [];
		}
	}

	public static function add_menu_page($menu_slug, $args=[]){
		self::$menu_pages[$menu_slug]	= $args;
	}

	public static function get_menu_pages(){
		$menu_pages	= self::$menu_pages;
		
		if(!empty(self::$option_settings)){
			foreach (self::$option_settings as $option_name => $args){
				if(!empty($args['post_type'])){
					$menu_pages[$args['post_type'].'s']['subs'][$option_name] = ['menu_title' => $args['title'],	'function'=>'option'];
				}
			}
		}

		return apply_filters('wpjam_pages', $menu_pages);
	}

	public static function get_filter_name($name='', $type=''){
		$filter	= str_replace('-', '_', $name);
		$filter	= str_replace('wpjam_', '', $filter);

		return 'wpjam_'.$filter.'_'.$type;
	}
}

class WPJAM_Path{
	private $page_key;
	private $page_type	= '';
	private $post_type	= '';
	private $taxonomy	= '';
	private $fields		= [];
	private $tabbars	= [];
	private $title		= '';
	private $paths		= [];
	private $callbacks	= [];
	private static $path_objs	= [];

	public function __construct($page_key, $args=[]){
		$this->page_key		= $page_key;
		$this->page_type	= $args['page_type'] ?? '';
		$this->title		= $args['title'] ?? '';

		if($this->page_type == 'post_type'){
			$this->post_type	= $args['post_type'] ?? $this->page_key;
		}elseif($this->page_type == 'taxonomy'){
			$this->taxonomy		= $args['taxonomy'] ?? $this->page_key;
		}
	}

	public function get_title(){
		return $this->title;
	}

	public function get_page_type(){
		return $this->page_type;
	}

	public function get_post_type(){
		return $this->post_type;
	}

	public function get_taxonomy(){
		return $this->taxonomy;
	}

	public function get_taxonomy_key(){
		if($this->taxonomy == 'category'){
			return 'cat';
		}elseif($this->taxonomy == 'post_tag'){
			return 'tag_id';
		}elseif($this->taxonomy){
			return $this->taxonomy.'_id';
		}else{
			return '';
		}
	}

	public function get_taxonomy_name(){
		if($this->taxonomy == 'category'){
			return 'category_name';
		}elseif($this->taxonomy == 'post_tag'){
			return 'tag';
		}elseif($this->taxonomy){
			return 'term';
		}else{
			return '';
		}
	}

	public function get_fields(){
		if($this->fields){
			return $this->fields ?? '';
		}else{
			$fields	= [];

			if($this->page_type == 'post_type'){
				$post_type_obj	= get_post_type_object($this->post_type);

				$fields[$this->post_type.'_id']	= ['title'=>'',	'type'=>'text',	'class'=>'all-options',	'data_type'=>'post_type',	'post_type'=>$this->post_type, 'placeholder'=>'请输入'.$post_type_obj->label.'ID或者输入关键字筛选',	'required'];
			}elseif($this->page_type == 'taxonomy'){
				$taxonomy_obj	= get_taxonomy($this->taxonomy);
				$taxonomy_key	= $this->get_taxonomy_key();

				if($taxonomy_obj->hierarchical){
					$levels		= $taxonomy_obj->levels ?? 0;
					$terms		= wpjam_get_terms(['taxonomy'=>$this->taxonomy,	'hide_empty'=>0], $levels);
					$terms		= wpjam_flatten_terms($terms);
					$options	= $terms ? wp_list_pluck($terms, 'name', 'id') : [];

					$fields[$taxonomy_key]	= ['title'=>'',	'type'=>'select',	'options'=>$options];
				}else{
					$fields[$taxonomy_key]	= ['title'=>'',	'type'=>'text',		'data_type'=>'taxonomy',	'taxonomy'=>$this->taxonomy];
				}
			}elseif($this->page_type == 'author'){
				$fields['author']	= ['title'=>'',	'type'=>'select',	'options'=>wp_list_pluck(get_users(['who'=>'authors']), 'display_name', 'ID')];
			}

			return $fields;
		}
	}

	public function get_tabbar($type){
		return $this->tabbars[$type] ?? false;
	}

	public function set_title($title){
		$this->title	= $title;
	}

	public function set_path($type, $path=''){
		$this->paths[$type]		= $path;
	}

	public function set_callback($type, $callback=''){
		$this->callbacks[$type]	= $callback;
	}

	public function set_fields($type, $fields=[]){
		$this->fields	= array_merge($this->fields, $fields);
	}

	public function set_tabbar($type, $tabbar=false){
		$this->tabbars[$type]	= $tabbar;
	}

	public function get_path($type, $args=[]){
		$args['path_type']	= $type;
		$args['page_key']	= $this->page_key;

		$callback	= $this->callbacks[$type] ?? '';

		if($callback){
			return call_user_func($callback, $args);
		}else{
			$path	= $this->paths[$type] ?? '';

			if($this->page_type == 'post_type'){
				$post_id	= isset($args[$this->post_type.'_id']) ? intval($args[$this->post_type.'_id']) : 0;

				if(empty($post_id)){
					$pt_object	= get_post_type_object($this->post_type);
					return new WP_Error('empty_'.$this->post_type.'_id', $pt_object->label.'ID不能为空并且必须为数字');
				}

				if($type == 'template'){
					return get_permalink($post_id);
				}else{
					if(strpos($path, '%post_id%')){
						$path	= str_replace('%post_id%', $post_id, $path);
					}
				}	
			}elseif($this->page_type == 'taxonomy'){
				$taxonomy_key	= $this->get_taxonomy_key();

				$term_id	= isset($args[$taxonomy_key]) ? intval($args[$taxonomy_key]) : 0;

				if(empty($term_id)){
					$tax_object	= get_taxonomy($this->taxonomy);
					return new WP_Error('empty_'.$taxonomy_key, $tax_object->label.'ID不能为空并且必须为数字');
				}

				if($type == 'template'){
					return get_term_link($term_id, $this->taxonomy);
				}else{
					if(strpos($path, '%term_id%')){
						$path	= str_replace('%term_id%', $term_id, $path);
					}
				}
			}elseif($this->page_type == 'author'){
				$author	= isset($args['author']) ? intval($args['author']) : 0;

				if(empty($author)){
					return new WP_Error('empty_author', '作者ID不能为空并且必须为数字。');
				}

				if($type == 'template'){
					return get_author_posts_url($author);
				}else{
					if(strpos($path, '%author%')){
						$path	= str_replace('%author%', $author, $path);
					}
				}
			}

			return $path;
		}
	}

	public function get_raw_path($type){
		return $this->paths[$type] ?? '';
	}

	public function has($type, $operator='AND', $strict=false){
		$types	= is_array($type) ? $type : [$type];

		foreach ($types as $type){
			if($operator == 'AND'){
				if($strict){
					if(empty($this->paths[$type]) && empty($this->callbacks[$type])){
						return false;
					}
				}else{
					if(!isset($this->paths[$type]) && !isset($this->callbacks[$type])){
						return false;
					}
				}
					
			}elseif($operator == 'OR'){
				if($strict){
					if(!empty($this->paths[$type]) || !empty($this->callbacks[$type])){
						return true;
					}
				}else{
					if(isset($this->paths[$type]) || isset($this->callbacks[$type])){
						return true;
					}
				}	
			}
		}

		if($operator == 'AND'){
			return true;
		}elseif($operator == 'OR'){
			return false;
		}
	}

	public static function parse_item($item, $path_type){
		$page_key	= $item['page_key'] ?? '';
		$parsed		= [];

		if($page_key == 'none'){
			if(!empty($item['video'])){
				$parsed['type']		= 'video';
				$parsed['video']	= $item['video'];
				$parsed['vid']		= WPJAM_API::get_qq_vid($item['video']);	
			}else{
				$parsed['type']		= 'none';
			}
		}elseif($page_key == 'external'){
			if($path_type == 'web'){
				$parsed['type']		= 'external';
				$parsed['url']		= $item['url'];	
			}
		}elseif($page_key == 'web_view'){
			if($path_type == 'weapp'){
				$parsed['type']		= 'web_view';
				$parsed['src']		= $item['src'];
			}else{
				$parsed['type']		= 'external';
				$parsed['url']		= $item['src'];
			}
		}elseif($page_key == 'mini_program'){
			if($path_type == 'weapp'){
				$parsed['type']		= 'mini_program';
				$parsed['appid']	= $item['appid'];
				$parsed['path']		= $item['path'] ? '/'.ltrim($item['path'], '/') : '';
			}
		}elseif($page_key == 'contact'){
			if($path_type == 'weapp'){
				$parsed['type']		= 'contact';
				$parsed['tips']		= $item['tips'];
			}
		}elseif($page_key){
			if($path_obj = self::get_instance($page_key)){
				$path	= $path_obj->get_path($path_type, $item);

				if(is_wp_error($path)){
					return $path;
				}elseif($path){
					$parsed['type']		= '';
					$parsed['page_key']	= $page_key;
					$parsed['path']		= $path;
				}
			}
		}

		return $parsed;
	}

	public static function parse_backup($item, $path_type){
		$page_key	= $item['page_key_backup'];
		$backup		= ['page_key'=>$page_key];

		$path_obj	= self::get_instance($page_key);

		if($path_obj && ($path_fields = $path_obj->get_fields())){
			foreach($path_fields as $field_key => $path_field){
				$backup[$field_key]	= $item[$field_key.'_backup'] ?? '';
			}
		}

		return self::parse_item($backup, $path_type);
	}

	public static function get_tabbar_options($path_type){
		$options	= [];

		if($path_objs	= self::$path_objs){
			foreach ($path_objs as $page_key => $path_obj){
				if($tabbar	= $path_obj->get_tabbar($path_type)){
					if(is_array($tabbar)){
						$text	= $tabbar['text'];
					}else{
						$text	= $path_obj->get_title();
					}

					$options[$page_key]	= $text;
				}
			}
		}

		return $options;
	}

	public static function get_path_fields($path_type, $for=''){
		if(empty($path_type)){
			return [];
		}
		
		$fields	= ['page_key'	=> ['title'=>'',	'type'=>'select',	'options'=>[]]];

		$path_types	= is_array($path_type) ? $path_type : [$path_type];

		if($path_objs = self::$path_objs){
			$strict	= boolval($for == 'qrcode');

			foreach ($path_objs as $page_key => $path_obj){
				if(!$path_obj->has($path_types, 'OR', $strict)){
					continue;
				}

				if($page_key == 'web_view' || $path_obj->has($path_types, 'AND')){
					$fields['page_key']['options'][$page_key]	= ['title'=>$path_obj->get_title(), 'shared'=>1];
				}else{
					$fields['page_key']['options'][$page_key]	= $path_obj->get_title();
				}

				if($path_fields = $path_obj->get_fields()){
					foreach($path_fields as $field_key => $path_field){

						if(isset($fields[$field_key])){
							$fields[$field_key]['data-page_key']	.= ','.$page_key;
						}else{
							$path_field['title']			= '';
							$path_field['data-page_key']	= $page_key;
							$fields[$field_key]				= $path_field;
						}
					}
				}
			}
		}

		if($for == 'qrcode'){
			$path_fields	= [
				'page_key_fieldset'	=> ['title'=>'页面',	'type'=>'fieldset',	'fields'=>$fields]
			];
		}else{
			$fields['page_key']['options']['none']	= ['title'=>'只展示不跳转', 'shared'=>1];

			$path_fields	= [
				'page_key_fieldset'	=> ['title'=>'页面',	'type'=>'fieldset',	'fields'=>$fields]
			];

			if($backup_fields	= self::get_backup_path_fields($path_types)){
				$path_fields['page_key_backup_fieldset']	= ['title'=>'备用',	'type'=>'fieldset',	'fields'=>$backup_fields];
			}
		}

		return apply_filters('wpjam_path_fields', $path_fields, $path_type, $for);
	}

	public static function get_backup_path_fields($path_types){
		$fields	= [];

		if(count($path_types) > 1){
			$fields['page_key_backup']	= ['title'=>'',	'type'=>'select',	'options'=>['none'=>'只展示不跳转'],	'description'=>'&emsp;跳转页面不生效时将启用备用页面'];

			if($path_objs = self::$path_objs){
				foreach ($path_objs as $page_key => $path_obj){
					if(!$path_obj->has($path_types, 'AND')){
						continue;
					}

					$path_fields	= $path_obj->get_fields();

					if($page_key == 'module_page' && $path_fields){
						$fields['page_key_backup']['options'][$page_key]	= $path_obj->get_title();

						foreach($path_fields as $field_key => $path_field){
							$path_field['data-page_key_backup']	= $page_key;
							$fields[$field_key.'_backup']		= $path_field;
						}
					}elseif(empty($path_fields)){
						$fields['page_key_backup']['options'][$page_key]	= $path_obj->get_title();
					}
				}
			}
		}

		return $fields;
	}

	public static function create($page_key, $args=[]){
		$path_obj	= self::get_instance($page_key);

		if(is_null($path_obj)){
			$path_obj	= new WPJAM_Path($page_key, $args);

			self::$path_objs[$page_key]	= $path_obj;
		}

		if(!empty($args['path_type'])){
			$path_type	= $args['path_type'];
			
			if(!empty($args['callback'])){
				$path_obj->set_callback($path_type, $args['callback']);
			}else{
				$path	= $args['path'] ?? '';
				$path_obj->set_path($path_type, $path);
			}

			if(!empty($args['fields'])){
				$path_obj->set_fields($path_type, $args['fields']);
			}

			$tabbar	= $args['tabbar'] ?? false;
			$path_obj->set_tabbar($path_type, $tabbar);
		}

		return $path_obj;
	}

	public static function get_instance($page_key){
		return self::$path_objs[$page_key] ?? null;
	}

	public static function get_by($args=[]){
		$path_objs	= [];

		if(self::$path_objs && $args){
			$path_type	= $args['path_type'] ?? '';
			$page_type	= $args['page_type'] ?? '';
			$post_type	= $args['post_type'] ?? '';
			$taxonomy	= $args['taxonomy'] ?? '';

			foreach (self::$path_objs as $page_key => $path_obj) {
				if($path_type && !$path_obj->has($path_type)){
					continue;
				}

				if($page_type && $path_obj->get_page_type() != $page_type){
					continue;
				}

				if($post_type && $path_obj->get_post_type() != $post_type){
					continue;
				}

				if($taxonomy && $path_obj->get_taxonomy() != $taxonomy){
					continue;
				}

				$path_objs[$page_key]	= $path_obj;
			}
		}

		return $path_objs;
	}

	public static function get_all(){
		return self::$path_objs;
	}
}