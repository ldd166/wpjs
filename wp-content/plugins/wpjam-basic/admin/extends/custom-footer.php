<?php
add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if($screen_base == 'post'){
		if(is_post_type_viewable($current_screen->post_type)){
			add_filter('wpjam_post_options', function ($wpjam_options){
			
				$wpjam_options['wpjam_custom_head_box'] = [
					'title'		=> '文章头部代码',	
					'fields'	=> [
						'custom_head'	=>['title'=>'',	'type'=>'textarea', 'description'=>'自定义文章代码可以让你在当前文章插入独有的 JS，CSS，iFrame 等类型的代码，让你可以对具体一篇文章设置不同样式和功能，展示不同的内容。']
					]
				];

				$wpjam_options['wpjam_custom_footer_box'] = [
					'title'		=> '文章底部代码',	
					'fields'	=> [
						'custom_footer'	=>['title'=>'',	'type'=>'textarea']
					]
				];

				return $wpjam_options;
			});
		}
	}
}, 10, 2);