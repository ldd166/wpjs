<?php
add_action('parse_request', function($wp){
	$module = $wp->query_vars['module'] ?? '';
	$action = $wp->query_vars['action'] ?? '';
 
 	if($module == 'json' && strpos($action, 'mag.') === 0){
 		return;
 	}

	wpjam_parse_query_vars($wp);
});

//设置 headers
add_action('send_headers', function ($wp){
	if(wpjam_basic_get_setting('x-frame-options')){
		header('X-Frame-Options: '.wpjam_basic_get_setting('x-frame-options'));
	}

	$module = $wp->query_vars['module'] ?? '';
	$action = $wp->query_vars['action'] ?? '';

	if($module){
		remove_action('template_redirect', 'redirect_canonical');

		if($module == 'json'){
			wpjam_send_origin_headers();
			wpjam_json_request($action);
		}elseif($module == 'txt'){
			if($action == 'robots'){
				return;
			}
			
			$value	= WPJAM_VerifyTXT::get_value_by_name($action);
			if($value){
				header('Content-Type: text/plain');
				echo $value;
			}else{
				wp_die('错误');
			}

			exit;
		}

		do_action('wpjam_module', $module, $action);
	}
});

// 当前用户处理
add_filter('determine_current_user', function($user_id){
	if($user_id || !wpjam_is_json_request()){
		return $user_id;
	}

	$wpjam_user	= wpjam_get_current_user();

	if($wpjam_user && !is_wp_error($wpjam_user) && !empty($wpjam_user['user_id'])){
		return $wpjam_user['user_id'];
	}

	return $user_id;
});

// 当前评论者
add_filter('wp_get_current_commenter', function($commenter){
	if(!empty($commenter['comment_author_email']) || !wpjam_is_json_request()){
		return $commenter;
	}

	$wpjam_user	= wpjam_get_current_user();

	if(is_wp_error($wpjam_user)){
		return $wpjam_user;
	}

	if(empty($wpjam_user) || empty($wpjam_user['user_email'])){
		return new WP_Error('bad_authentication', '无权限');
	}

	$commenter['comment_author_email']	= $wpjam_user['user_email'];
	$commenter['comment_author']		= $wpjam_user['nickname'];

	return $commenter;
});

add_filter('template_include', function ($template){
	$module	= get_query_var('module');
	$action	= get_query_var('action');

	if($module){
		$action = ($action == 'new' || $action == 'add')?'edit':$action;

		if($action){
			$wpjam_template = STYLESHEETPATH.'/template/'.$module.'/'.$action.'.php';
		}else{
			$wpjam_template = STYLESHEETPATH.'/template/'.$module.'/index.php';
		}

		$wpjam_template		= apply_filters('wpjam_template', $wpjam_template, $module, $action);

		if(is_file($wpjam_template)){
			return $wpjam_template;
		}else{
			wp_die('路由错误！');
		}
	}

	return $template;
});

add_action('wpjam_api_template_redirect', function($json){
	remove_filter('the_excerpt', 'convert_chars');
	remove_filter('the_excerpt', 'wpautop');
	remove_filter('the_excerpt', 'shortcode_unautop');

	remove_filter('the_title', 'convert_chars');

	// add_filter('the_password_form',	function($output){
	// 	if(get_queried_object_id() == get_the_ID()){
	// 		return '';
	// 	}else{
	// 		return $output;
	// 	}
	// });

	if($json == 'token' || $json == 'token.grant'){
		wpjam_api_validate_quota('token.grant', 1000);

		$appid	= wpjam_get_parameter('appid',	['required' => true]);
		$secret	= wpjam_get_parameter('secret', ['required' => true]);

		$token	= WPJAM_Grant::generate_access_token($appid, $secret);

		if(is_wp_error($token)){
			wpjam_send_json($token);
		}

		wpjam_send_json([
			'errcode'		=> 0,
			'access_token'	=> $token,
			'expires_in'	=> 7200
		]);
	}
});

function wpjam_parse_query_vars($wp){
	$query_vars	= $wp->query_vars;

	$tax_query	= [];

	if(!empty($query_vars['tag_id']) && $query_vars['tag_id'] == -1){
		$tax_query[]	= [
			'taxonomy'	=> 'post_tag',
			'field'		=> 'term_id',
			'operator'	=> 'NOT EXISTS'
		];

		unset($query_vars['tag_id']);
	}

	if(!empty($query_vars['cat']) && $query_vars['cat'] == -1){
		$tax_query[]	= [
			'taxonomy'	=> 'category',
			'field'		=> 'term_id',
			'operator'	=> 'NOT EXISTS'
		];

		unset($query_vars['cat']);
	}

	if($taxonomy_objs = get_taxonomies(['_builtin'=>false], 'objects')){
		foreach ($taxonomy_objs as $taxonomy => $taxonomy_obj){
			$tax_key	= $taxonomy.'_id';

			if(empty($query_vars[$tax_key])){
				continue;
			}

			$current_term_id	= $query_vars[$tax_key];
			unset($query_vars[$tax_key]);

			if($current_term_id == -1){
				$tax_query[]	= [
					'taxonomy'	=> $taxonomy,
					'field'		=> 'term_id',
					'operator'	=> 'NOT EXISTS'
				];
			}else{
				$tax_query[]	= [
					'taxonomy'	=> $taxonomy,
					'terms'		=> [$current_term_id],
					'field'		=> 'term_id',
				];
			}
		}
	}

	if(!empty($query_vars['taxonomy']) && empty($query_vars['term']) && !empty($query_vars['term_id'])){
		$tax_query[]	= [
			'taxonomy'	=> $query_vars['taxonomy'],
			'terms'		=> [$query_vars['term_id']],
			'field'		=> 'term_id',
		];
	}

	if($tax_query){
		if(!empty($query_vars['tax_query'])){
			$query_vars['tax_query'][]	= $tax_query;
		}else{
			$query_vars['tax_query']	= $tax_query;
		}

		$wp->set_query_var('tax_query', $tax_query);
	}

	$date_query	= $query_vars['date_query'] ?? [];

	if(!empty($query_vars['cursor'])){
		$date_query[]	= ['before' => get_date_from_gmt(date('Y-m-d H:i:s', $query_vars['cursor']))];
	}

	if(!empty($query_vars['since'])){
		$date_query[]	= ['after' => get_date_from_gmt(date('Y-m-d H:i:s', $query_vars['since']))];
	}

	if($date_query){
		$wp->set_query_var('date_query', $date_query);
	}
}

function wpjam_get_current_user(){
	return apply_filters('wpjam_current_user', null);
}

function wpjam_is_json_request(){
	if(get_option('permalink_structure')){
		if(preg_match("/\/api\/(.*)\.json/", $_SERVER['REQUEST_URI'])){ 
			return true;
		}
	}else{
		if(isset($_GET['module']) && $_GET['module'] == 'json'){
			return true;
		}
	}
		
	return false;
}

function wpjam_json_request($action){
	if(!wpjam_doing_debug()){ 
		if(wp_is_jsonp_request()){
			@header('Content-Type: application/javascript; charset='.get_option('blog_charset'));
		}else{
			@header('Content-Type: application/json; charset='.get_option('blog_charset'));	
		}
	}
		
	if(strpos($action, 'mag.') !== 0){
		return;
	}

	global $wp, $wpjam_json;
			
	$wpjam_json	= str_replace(['mag.','/'], ['','.'], $action);

	do_action('wpjam_api_template_redirect', $wpjam_json);

	$api_setting	= WPJAM_Core::get_api($wpjam_json);

	if(!$api_setting){
		wpjam_send_json([
			'errcode'	=> 'api_not_defined',
			'errmsg'	=> '接口未定义！',
		]);
	}

	if(!empty($api_setting['grant'])){
		wpjam_api_validate_access_token();
	}

	if(!empty($api_setting['quota'])){
		wpjam_api_validate_quota($wpjam_json, $api_setting['quota']);
	}
	
	$wpjam_user	= wpjam_get_current_user();

	if(is_wp_error($wpjam_user)){
		if(!empty($api_setting['auth'])){
			wpjam_send_json($wpjam_user);
		}else{
			$wpjam_user	= null;
		}
	}elseif(is_null($wpjam_user)){
		if(!empty($api_setting['auth'])){
			wpjam_send_json([
				'errcode'	=>'bad_authentication', 
				'errmsg'	=>'无权限'
			]);
		}
	}

	$response	= ['errcode'=>0];

	$response['current_user']	= $wpjam_user;
	$response['page_title']		= $api_setting['page_title'] ?? '';
	$response['share_title']	= $api_setting['share_title'] ?? '';
	$response['share_image']	= !empty($api_setting['share_image']) ? wpjam_get_thumbnail($api_setting['share_image'], '500x400') : '';

	foreach ($api_setting['modules'] as $module){
		if(!$module['type'] || !$module['args']){
			continue;
		}
		
		if(is_array($module['args'])){
			$args = $module['args'];
		}else{
			$args = wpjam_parse_shortcode_attr(stripslashes_deep($module['args']), 'module');
		}

		$module_type	= $module['type'];
		$module_action	= $args['action'] ?? '';
		$output			= $args['output'] ?? '';

		if(in_array($module_type, ['post_type', 'taxonomy', 'media', 'setting', 'other'])){
			$module_template	= WPJAM_BASIC_PLUGIN_DIR.'api/'.$module_type.'.php';
		}else{
			$module_template	= '';
		}

		$module_template	= apply_filters('wpjam_api_template_include', $module_template, $module_type, $module);

		if($module_template && is_file($module_template)){
			include $module_template;
		}
	}

	$response = apply_filters('wpjam_json', $response, $api_setting, $wpjam_json);

	wpjam_send_json($response);
}

function wpjam_api_validate_quota($json='', $max_times=1000){
	$json	= $json ?: wpjam_get_json();
	$today	= date('Y-m-d', current_time('timestamp'));
	$times	= wp_cache_get($json.':'.$today, 'wpjam_api_times');
	$times	= $times ?: 0;

	if($times < $max_times){
		wp_cache_set($json.':'.$today, $times+1, 'wpjam_api_times', DAY_IN_SECONDS);
	}else{
		wpjam_send_json([
			'errcode'	=> 'exceed_quota',
			'errmsg'	=> 'API 调用次数超限'
		]);
	}	
}

function wpjam_api_validate_access_token(){
	if(!isset($_GET['access_token']) && is_super_admin()){
		return true;
	}

	$token	= wpjam_get_parameter('access_token', ['required'=>true]);

	if(!WPJAM_Grant::validate_access_token($token)){
		wpjam_send_json([
			'errcode'	=> 'invalid_access_token',
			'errmsg'	=> '非法 Access Token'
		]);
	}
}

function wpjam_is_module($module='', $action=''){
	$current_module	= get_query_var('module');
	$current_action	= get_query_var('action');

	// 没设置 module
	if(!$current_module){
		return false;
	}
	
	// 不用确定当前是什么 module
	if(!$module){
		return true;
	}
	
	if($module != $current_module){
		return false;
	}

	if(!$action){
		return true;
	}

	if($action != $current_action){
		return false;
	}
	
	return true;
}

function wpjam_send_origin_headers(){
	header('X-Content-Type-Options: nosniff');

	$origin = get_http_origin();

	if ( $origin ) {
		// Requests from file:// and data: URLs send "Origin: null"
		if ( 'null' !== $origin ) {
			$origin = esc_url_raw( $origin );
		}

		@header( 'Access-Control-Allow-Origin: ' . $origin );
		@header( 'Access-Control-Allow-Methods: GET, POST' );
		@header( 'Access-Control-Allow-Credentials: true' );
		@header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
		@header( 'Vary: Origin' );

		if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			exit;
		}
	}
	
	if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
		status_header( 403 );
		exit;
	}
}

function wpjam_get_json(){
	global $wpjam_json;

	return $wpjam_json ?? '';
}

function wpjam_is_json($json=''){
	$wpjam_json = wpjam_get_json();

	if(empty($wpjam_json)){
		return false;
	}

	if($json){
		return $wpjam_json == $json;
	}else{
		return true;
	}
}

function is_module($module='', $action=''){
	return wpjam_is_module($module, $action);
}

function is_wpjam_json($json=''){
	return wpjam_is_json($json);
}