<?php
add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if($screen_base == 'edit'){
		if(is_post_type_viewable($current_screen->post_type)){
			$post_type	= $current_screen->post_type;
			include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/post-duplicate.php';
		}
	}
}, 1, 2);