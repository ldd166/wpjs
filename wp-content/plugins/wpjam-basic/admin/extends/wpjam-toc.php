<?php
add_filter('wpjam_posts_page_enable', '__return_true');

add_action('wpjam_plugin_page_load', function($plugin_page){
	if($plugin_page == 'wpjam-posts'){
		add_filter('wpjam_posts_tabs', function($tabs){
			$tabs['toc']	= [
				'title'			=>'文章目录',
				'function'		=>'option', 
				'option_name'	=>'wpjam-basic', 
				'tab_file'		=>WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-toc.php'
			];
			
			return $tabs;
		});
	}
});

add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if($screen_base == 'post' && $current_screen->post_type != 'attachment'){
		if(wpjam_basic_get_setting('toc_individual')){
			include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/post-toc.php';
		}
	}
}, 10, 2);