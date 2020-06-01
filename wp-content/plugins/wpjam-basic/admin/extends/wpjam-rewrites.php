<?php
add_filter('wpjam_basic_sub_pages',function($subs){
	$subs['wpjam-rewrites']	= [
		'menu_title'	=> 'Rewrites',
		'function'		=> 'tab',
		'page_file'		=> WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-rewrites.php',
		'summary'		=> 'Rewrites 扩展让可以优化现有 Rewrites 规则和添加额外的 Rewrite 规则，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-rewrite/" target="_blank">Rewrites 扩展</a>。'
	];
	return $subs;
});


