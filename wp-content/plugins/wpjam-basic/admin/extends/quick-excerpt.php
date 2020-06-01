<?php
add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if($screen_base == 'edit'){
		if(post_type_supports($current_screen->post_type, 'excerpt')){
			$post_type	= $current_screen->post_type;
			include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/quick-excerpt.php';	
		}
	}
}, 1, 2);