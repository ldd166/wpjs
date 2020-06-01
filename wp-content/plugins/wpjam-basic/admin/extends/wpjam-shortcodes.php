<?php
add_filter('wpjam_basic_sub_pages',function($subs){
	$subs['wpjam-shortcodes']	= [
		'menu_title'	=> '短代码',
		'function'		=> 'list',
		'page_file'		=> WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-shortcodes.php',
		'summary'		=> '短代码扩展罗列出系统中所有的短代码，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-basic-shortcode/" target="_blank">短代码扩展</a>。'
	];
	
	return $subs;
});


