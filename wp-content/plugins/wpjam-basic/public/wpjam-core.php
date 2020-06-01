<?php
if(is_admin()){
	include WPJAM_BASIC_PLUGIN_DIR.'admin/core/load.php';
	include WPJAM_BASIC_PLUGIN_DIR.'admin/core/hooks.php';
	include WPJAM_BASIC_PLUGIN_DIR.'admin/core/functions.php';
	include WPJAM_BASIC_PLUGIN_DIR.'admin/core/notice.php';
	include WPJAM_BASIC_PLUGIN_DIR.'admin/core/stats.php';
	include WPJAM_BASIC_PLUGIN_DIR.'admin/core/verify.php';
}

function wpjam_add_menu_page($menu_slug, $args=[]){
	WPJAM_Core::add_menu_page($menu_slug, $args);
}

function wpjam_register_post_type($post_type, $args=[]){
	WPJAM_Core::register_post_type($post_type, $args);
}

function wpjam_register_taxonomy($taxonomy, $args=[]){
	WPJAM_Core::register_taxonomy($taxonomy, $args);
}

function wpjam_register_post_option($meta_box, $args=[]){
	WPJAM_Core::register_post_option($meta_box, $args);
}

function wpjam_register_term_option($key, $args=[]){
	WPJAM_Core::register_term_option($key, $args);
}

function wpjam_register_option($option_name, $args=[]){
	WPJAM_Core::register_option($option_name, $args);
}

function wpjam_register_api($json, $args=[]){
	WPJAM_Core::register_api($json, $args);
}

function wpjam_get_post_options($post_type){
	return WPJAM_Core::get_post_options($post_type);
}

function wpjam_get_post_fields($post_type){
	return WPJAM_Core::get_post_fields($post_type);
}

function wpjam_get_term_options($taxonomy){
	return WPJAM_Core::get_term_options($taxonomy);
}

function wpjam_get_option_setting($option_name){
	return WPJAM_Core::get_option($option_name);
}

function wpjam_get_filter_name($name='', $type=''){
	return WPJAM_Core::get_filter_name($name, $type);
}



function wpjam_register_path($page_key, $args=[]){
	if(wp_is_numeric_array($args)){
		foreach($args as $i=> $item){
			WPJAM_Path::create($page_key, $item);
		}

		return true;
	}else{
		return WPJAM_Path::create($page_key, $args);
	}	
}

function wpjam_get_path_obj($page_key){
	return WPJAM_Path::get_instance($page_key);
}

function wpjam_get_path_objs($path_type){
	return WPJAM_Path::get_by(['path_type'=>$path_type]);
}

function wpjam_get_tabbar_options($path_type){
	return WPJAM_Path::get_tabbar_options($path_type);
}

function wpjam_get_path_fields($path_type, $for=''){
	return WPJAM_Path::get_path_fields($path_type, $for);
}

function wpjam_get_path($path_type, $page_key,  $args=[]){
	$path_obj	= wpjam_get_path_obj($page_key);

	return $path_obj ? $path_obj->get_path($path_type, $args) : '';
}

function wpjam_parse_path_item($item, $platforms=[]){
	if($platforms){
		if(count($platforms) > 1){
			$path_type	= wpjam_get_current_platform($platforms);	
		}else{
			$path_type	= current($platforms);
		}
	}else{
		$path_type	= wpjam_get_current_platform();
	}

	$parsed	= WPJAM_Path::parse_item($item, $path_type);

	if(count($platforms) > 1){
		if((empty($parsed) || is_wp_error($parsed))  && !empty($item['page_key_backup'])){
			$parsed	= WPJAM_Path::parse_backup($item, $path_type);
		}
	}

	if(empty($parsed) || is_wp_error($parsed)){
		$parsed	= ['type'=>'none'];
	}

	return $parsed;
}

function wpjam_render_path_item($item, $text, $platforms=[]){
	$parsed = wpjam_parse_path_item($item, $platforms);
	
	if($parsed['type'] == 'none'){
		return $text;
	}elseif($parsed['type'] == 'external'){
		return '<a href_type="web_view" href="'.$parsed['url'].'">'.$text.'</a>';
	}elseif($parsed['type'] == 'web_view'){
		return '<a href_type="web_view" href="'.$parsed['src'].'">'.$text.'</a>';
	}elseif($parsed['type'] == 'mini_program'){
		return '<a href_type="mini_program" href="'.$parsed['path'].'" appid="'.$parsed['appid'].'">'.$text.'</a>';
	}elseif($parsed['type'] == 'contact'){
		return '<a href_type="contact" href="" tips="'.$parsed['tips'].'">'.$text.'</a>';
	}elseif($parsed['type'] == ''){
		return '<a href_type="path" page_key="'.$parsed['page_key'].'" href="'.$parsed['path'].'">'.$text.'</a>';
	}
}

function wpjam_validate_path_item($item, $path_types){
	if(count($path_types) > 1){
		$page_key	= $item['page_key'];
		$path_obj	= wpjam_get_path_obj($page_key);

		if($path_obj && $path_obj->has($path_types, 'AND')){
			$backup_check	= false;
		}else{
			$backup_check	= true;
		}
	}else{
		$backup_check	= false;
	}	

	foreach ($path_types as $path_type) {
		$parsed	= WPJAM_Path::parse_item($item, $path_type);

		if(!$backup_check){
			if(empty($parsed)){
				return new WP_Error('invalid_page_key', '页面无效');
			}elseif(is_wp_error($parsed)){
				return $parsed;
			}
		}else{
			if(is_wp_error($parsed)){
				return $parsed;
			}
		}
	}

	if($backup_check){
		foreach ($path_types as $path_type) {
			$parsed	= WPJAM_Path::parse_bacup($item, $path_type);

			if(empty($parsed)){
				return new WP_Error('invalid_backup_page_key', '备用页面无效');
			}elseif(is_wp_error($parsed)){
				return $parsed;
			}
		}
	}

	return $parsed;
}