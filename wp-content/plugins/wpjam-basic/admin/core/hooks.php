<?php
add_action('admin_page_access_denied', function(){
	if((is_multisite() && is_user_member_of_blog(get_current_user_id(), get_current_blog_id())) || !is_multisite()){
		wp_die(__( 'Sorry, you are not allowed to access this page.' ).'<a href="'.admin_url().'">返回首页</a>', 403);
	}
});

add_filter('is_protected_meta', function($protected, $meta_key){
	if(in_array($meta_key, ['views', 'likes', 'favs'])){
		return true;
	}else{
		return $protected;
	}
}, 10, 2);



add_filter('removable_query_args', function($removable_query_args){
	return array_merge($removable_query_args, ['added', 'duplicated', 'unapproved',	'unpublished', 'published', 'geted', 'created', 'synced']);
});

add_filter('register_post_type_args', function($args, $post_type){
	if(!empty($args['_builtin']) || empty($args['show_ui'])){
		return $args;
	}

	$args_labels	= $args['labels'] ?? [];

	add_filter("post_type_labels_".$post_type, function($labels) use($args_labels){
		$labels		= (array)$labels;
		$label_name	= $labels['name'];

		if(empty($args_labels['all_items'])){
			$labels['all_items']	= '所有'.$labels['all_items'];
		}

		foreach ($labels as $key => &$label) {
			if($label == $label_name || !empty($args_labels[$key])){
				continue;
			}

			$label	= str_replace(
				['文章', '页面', 'post', 'Post', '撰写新', '写新', '写'], 
				[$label_name, $label_name, $label_name, ucfirst($label_name), '新建', '新建', '新建'], 
				$label
			);
		}

		return $labels;
	});

	return $args;
}, 10, 2);

add_filter('register_taxonomy_args',function($args, $taxonomy){
	if(!empty($args['_builtin']) || empty($args['show_ui'])){
		return $args;
	}

	add_filter('taxonomy_labels_'.$taxonomy, function($labels){
		$labels		= (array)$labels;
		$label_name	= $labels['name'];

		return array_map(function($label) use ($label_name){
			if($label == $label_name) return $label;
			return str_replace(
				['目录', '分类', '标签', 'categories', 'Categories', 'Category', 'Tag', 'tag'], 
				['', $label_name, $label_name, $label_name, ucfirst($label_name).'s', ucfirst($label_name), ucfirst($label_name), $label_name], 
				$label
			);
		}, $labels);
	});

	return $args;
}, 10, 2);

add_action('init', function(){
	if(!wpjam_is_scheduled_event('wpjam_remove_invalid_crons')) {
		wp_schedule_event(time(), 'daily', 'wpjam_remove_invalid_crons');
	}
});

// 如果插件页面
add_filter('set-screen-option', function($status, $option, $value){
	return isset($_GET['page']) ? $value : $status;
}, 10, 3);

add_action('wp_ajax_wpjam-query', function(){
	$args	= $_POST;
	unset($args['action']);
	unset($args['data_type']);

	$data_type	= $_POST['data_type'];
	if($data_type == 'post_type'){
		if(!empty($args['search'])){
			$args['s']	= $args['search'];
		}

		$args['posts_per_page']	= $args['posts_per_page'] ?? 10;
		$args['post_status']	= $args['post_status'] ?? 'publish';

		$query	= wpjam_query($args);
		
		$posts	= array_map(function($post){
			return wpjam_get_post($post->ID);
		}, $query->posts);

		wpjam_send_json(['posts'=>$posts]);
	}elseif($data_type == 'taxonomy'){
		$args['number']		= $args['number'] ?? 10;
		$args['hide_empty']	= $args['hide_empty'] ?? 0;
		
		$terms	= wpjam_get_terms($args, -1);

		wpjam_send_json(['terms'=>$terms]);
	}
});

add_action('wp_ajax_wpjam-page-action', function(){
	global $plugin_page;

	$action	= $_POST['page_action'];
	$nonce	= $_POST['_ajax_nonce'];

	if(!wp_verify_nonce($nonce, $plugin_page.'-'.$action)){
		wpjam_send_json([
			'errcode'	=> 'invalid_nonce',
			'errmsg'	=> '非法操作'
		]);
	}

	$action_type	= sanitize_key($_POST['page_action_type']);

	do_action('wpjam_page_action', $action, $action_type);

	$ajax_response	= wpjam_get_filter_name($plugin_page, 'ajax_response');
	$ajax_response	= apply_filters('wpjam_page_ajax_response', $ajax_response, $plugin_page, $action, $action_type);

	if(is_callable($ajax_response)){
		$result	= call_user_func($ajax_response, $action);
		if(is_wp_error($result)){
			wpjam_send_json($result);
		}else{
			$result	= is_array($result) ? $result : [];
			wpjam_send_json($result);
		}
	}else{
		wpjam_send_json([
			'errcode'	=> 'invalid_ajax_response',
			'errmsg'	=> '非法回调函数'
		]);
	}
});

add_action('admin_enqueue_scripts', function(){
	global $pagenow, $current_screen;

	if($pagenow == 'customize.php'){
		return;
	}

	$plugin_data	= get_plugin_data(WPJAM_BASIC_PLUGIN_FILE);
	$ver			= $plugin_data['Version'];

	// wp_enqueue_script('jquery-ui-button');
	add_thickbox();

	$post = get_post();
	if(!$post && !empty($GLOBALS['post_ID'])){
		$post = $GLOBALS['post_ID'];
	}

	wp_enqueue_media(['post'=>$post]);
	
	wp_enqueue_style('editor-buttons');
	wp_enqueue_style('wp-color-picker');
	
	wp_enqueue_script('wp-color-picker');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('jquery-ui-autocomplete');

	wp_enqueue_script('raphael',	'https://cdn.staticfile.org/raphael/2.3.0/raphael.min.js', [], $ver);
	wp_enqueue_script('morris',		'https://cdn.staticfile.org/morris.js/0.5.1/morris.min.js', [], $ver);
	wp_enqueue_style('morris',		'https://cdn.staticfile.org/morris.js/0.5.1/morris.css', [], $ver);

	wp_enqueue_style('wpjam-style',	WPJAM_BASIC_PLUGIN_URL.'/static/style.css', [], $ver);

	if($pagenow != 'plugins.php'){
		wp_enqueue_script('wpjam-script',	WPJAM_BASIC_PLUGIN_URL.'/static/script.js', ['jquery','jquery-ui-core','thickbox'], $ver);
		wp_enqueue_script('wpjam-form',		WPJAM_BASIC_PLUGIN_URL.'/static/form.js',   ['jquery','jquery-ui-core','wp-backbone','media-views','wp-color-picker'], $ver);
	}

	global $plugin_page, $current_tab, $current_list_table, $current_option;

	$item_prefix	= '';

	if($plugin_page){
		if(isset($current_option)){
			$params	= [];
		}else{
			$params	= $_REQUEST;

			foreach (['page', 'tab', '_wp_http_referer', '_wpnonce'] as $query_key) {
				unset($params[$query_key]);
			}

			$params	= $params ? array_map('sanitize_textarea_field', $params) : [];
		}
	}else{
		$params	= null;	

		if(in_array($pagenow, ['upload.php', 'edit.php'])){
			$item_prefix	= '#post-';
		}elseif($pagenow == 'edit-tags.php'){
			$item_prefix	= '#tag-';
		}elseif($pagenow == 'users.php'){
			$item_prefix	= '#user-';
		}
	}

	$params	= $params ?: new stdClass();

	wp_localize_script('wpjam-script', 'wpjam_page_setting', [
		'screen_id'			=> $current_screen->id,
		'screen_base'		=> $current_screen->base,
		'plugin_page'		=> $plugin_page ?? null,
		'current_tab'		=> $current_tab ?? null,
		'current_list_table'=> $current_list_table ?? null,
		'current_option'	=> $current_option ?? null,
		'params'			=> $params,
		'item_prefix'		=> $item_prefix
	]);
});

//模板 JS
add_action('print_media_templates', function (){ ?>

	<div id="tb_modal" style="display:none; background: #f1f1f1;"></div>

	<script type="text/html" id="tmpl-wpjam-img">
	<img style="{{ data.img_style }}" src="{{ data.img_url }}{{ data.thumb_args }}" alt="" /><a href="javascript:;" data-bg_class="{{ data.bg_class }}"  class="del-img dashicons dashicons-no-alt"></a>
	</script>

	<script type="text/html" id="tmpl-wpjam-mu-img">
	<div class="mu-img mu-item"><img width="100" src="{{ data.img_url }}{{ data.thumb_args }}"><input type="hidden" name="{{ data.input_name }}" id="{{ data.key }}_{{ data.i }}" value="{{ data.img_value }}" /><a href="javascript:;" class="del-item dashicons dashicons-no-alt"></a></div>
	</script>

	<script type="text/html" id="tmpl-wpjam-mu-file">
	<div class="mu-item"><input type="url" name="{{ data.input_name }}" id="{{ data.input_id }}" class="regular-text" value="{{ data.img_url }}" /> <a href="javascript:;" class="button del-item">删除</a>  <span class="dashicons dashicons-menu"></span></div>
	</script>

	<?php echo WPJAM_Field::get_field_tmpls();
});



