<?php
// 角色管理菜单
add_filter('wpjam_pages', function ($wpjam_pages){
	$capability	= (is_multisite())?'manage_site':'manage_options';

	$wpjam_pages['users']['subs']['roles']	=  [
		'menu_title'	=>'角色管理',
		'capability'	=>$capability,
		'function'		=>'list',
		'page_file'		=> WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-roles.php',
	];
	return $wpjam_pages;
},12);

add_action('wpjam_builtin_page_load', function ($screen_base){
	if(in_array($screen_base, ['user-edit', 'profile'])){
		include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/user-capabilities.php';
	}
});



