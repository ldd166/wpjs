<?php
add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if($screen_base == 'post' && $current_screen->post_type != 'attachment'){
		include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/post-type-switcher.php';
	}
}, 10, 2);