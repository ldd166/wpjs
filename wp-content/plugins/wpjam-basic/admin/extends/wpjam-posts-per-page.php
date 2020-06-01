<?php
add_filter('wpjam_posts_page_enable', '__return_true');

add_action('wpjam_plugin_page_load', function($plugin_page){
	if($plugin_page == 'wpjam-posts'){
		add_filter('wpjam_posts_tabs', function($tabs){
			$tabs['posts-per-page']	= [
				'title'			=>'文章数量',
				'function'		=>'option', 
				'option_name'	=>'wpjam-posts-per-page', 
				'tab_file'		=>WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-posts-per-page.php'
			];

			$post_types = get_post_types(['exclude_from_search'=>false],'objects');

			unset($post_types['page']);
			unset($post_types['attachment']);

			if($post_types){
				$tabs['post_types-per-page']	= [
					'title'			=>'文章类型',
					'function'		=>'option', 
					'option_name'	=>'wpjam-posts-per-page', 
					'tab_file'		=>WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-posts-per-page.php'
				];
			}
			
			return $tabs;
		});
	}
});

add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if($screen_base == 'edit-tags' && is_taxonomy_hierarchical($current_screen->taxonomy) && wpjam_get_posts_per_page($current_screen->taxonomy.'_individual')){
		$taxonomy	= $current_screen->taxonomy;
		require WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/term-posts-per-page.php';	
	}
}, 10, 2);