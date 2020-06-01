<?php
if(empty($module_action)){
	wpjam_send_json([
		'errcode'	=> 'empty_action',
		'errmsg'	=> '没有设置 action',
	]);
}

global $wpjam_query_vars;	// 两个 post 模块的时候干扰。。。。

if(empty($wpjam_query_vars)){
	$wpjam_query_vars	= $wp->query_vars; 
}else{
	$wp->query_vars		= $wpjam_query_vars;
	$wpjam_query_vars	= null;
}

$post_type	= $args['post_type'] ?? wpjam_get_parameter('post_type');

if($module_action == 'upload'){
	$post_template	= WPJAM_BASIC_PLUGIN_DIR.'api/media.php';
}elseif(in_array($module_action, ['list', 'get'])){
	$post_template	= WPJAM_BASIC_PLUGIN_DIR.'api/post.'.$module_action.'.php';
}else{
	$post_template	= apply_filters('wpjam_api_post_template', '', $module_action, $post_type);
}

if($post_template && is_file($post_template)){
	include $post_template;
}