<?php
add_filter('wpjam_basic_sub_pages', function($subs){
	$subs['301-redirects']	= [
		'menu_title'	=>'301跳转',
		'function'		=>'list',
		'page_file'		=> WPJAM_BASIC_PLUGIN_DIR.'admin/pages/301-redirects.php'
	];

	return $subs;
});

