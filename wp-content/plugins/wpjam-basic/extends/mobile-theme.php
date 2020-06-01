<?php
/*
Plugin Name: 移动主题
Plugin URI: http://blog.wpjam.com/project/wpjam-basic/
Description: 给移动设备设置单独的主题，以及在PC环境下进行移动主题的配置。
Version: 1.0
*/

add_action('plugins_loaded', function(){
	if(wp_is_mobile() || (is_admin() && wpjam_basic_get_setting('admin_mobile_theme'))){
		
		if(wpjam_basic_get_setting('mobile_stylesheet')){
			add_filter('stylesheet', function($stylesheet){
				return wpjam_basic_get_setting('mobile_stylesheet');
			});
		}
		
		if(wpjam_basic_get_setting('mobile_template')){
			add_filter('template', function($template){
				return wpjam_basic_get_setting('mobile_template');
			});
		}
	}	
}, 0);

