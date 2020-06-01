<?php
if(wpjam_basic_get_setting('disable_auto_update')){
	remove_action('admin_init', '_maybe_update_core');
	remove_action('admin_init', '_maybe_update_plugins');
	remove_action('admin_init', '_maybe_update_themes');
}

if(wpjam_basic_get_setting('remove_help_tabs')){  
	add_action('in_admin_header', function(){
		global $current_screen;
		$current_screen->remove_help_tabs();
	});
}

if(wpjam_basic_get_setting('remove_screen_options')){  
	add_filter('screen_options_show_screen', '__return_false');
	add_filter('hidden_columns', '__return_empty_array');
}

if(wpjam_basic_get_setting('admin_footer')){
	add_filter('admin_footer_text', function($text){
		return wpjam_basic_get_setting('admin_footer');
	});
}

if(wpjam_basic_get_setting('disable_privacy')){
	add_action('admin_menu', function(){
		remove_submenu_page('options-general.php', 'options-privacy.php');
		remove_submenu_page('tools.php', 'export-personal-data.php');
		remove_submenu_page('tools.php', 'erase-personal-data.php');
	},11);

	add_action('admin_init', function(){
		remove_action( 'admin_init', array( 'WP_Privacy_Policy_Content', 'text_change_check' ), 100 );
		remove_action( 'edit_form_after_title', array( 'WP_Privacy_Policy_Content', 'notice' ) );
		remove_action( 'admin_init', array( 'WP_Privacy_Policy_Content', 'add_suggested_content' ), 1 );
		remove_action( 'post_updated', array( 'WP_Privacy_Policy_Content', '_policy_page_updated' ) );
		remove_filter( 'list_pages', '_wp_privacy_settings_filter_draft_page_titles', 10, 2 );
	},1);
}

if(wpjam_basic_get_setting('timestamp_file_name')){
	// 防止重名造成大量的 SQL 请求
	add_filter('wp_handle_sideload_prefilter', function($file){
		$file['name']	= time().'-'.$file['name'];
		return $file;
	});

	add_filter('wp_handle_upload_prefilter', function($file){
		$file['name']	= time().'-'.$file['name'];
		return $file;
	});
}

// WordPress 国内镜像
if(wpjam_basic_get_setting('wordpress_mirror')){
	add_filter('site_transient_update_core', function($value){
		if(isset($value->updates)){
			foreach ($value->updates as &$update) {
				if($update->locale == 'zh_CN'){
					$update->download		= 'https://www.xintheme.com/go/wordpress';
					$update->packages->full	= 'https://www.xintheme.com/go/wordpress';
				}
			}
		}

		return $value;
	});

	add_filter('site_transient_update_plugins', function($value){
		if(isset($value->response) && isset($value->response['wpjam-basic/wpjam-basic.php'])){
			$value->response['wpjam-basic/wpjam-basic.php']->package	= 'https://www.xintheme.com/go/wpjam-basic';
		}
		return $value;
	});
}

add_action('admin_head', function(){
	remove_action('admin_bar_menu', 'wp_admin_bar_wp_menu', 10);
	
	add_action('admin_bar_menu', function ($wp_admin_bar){
		if(wpjam_basic_get_setting('admin_logo')){
			$title 	= '<img src="'.wpjam_get_thumbnail(wpjam_basic_get_setting('admin_logo'),40,40).'" style="height:20px; padding:6px 0">';
		}else{
			$title	= '<span class="ab-icon"></span>';
		}
		$wp_admin_bar->add_menu( array(
			'id'    => 'wp-logo',
			'title' => $title,
			'href'  => self_admin_url(),
			'meta'  => array(
				'title' => __('About'),
			),
		) );
	});

	echo wpjam_basic_get_setting('admin_head');

	if(wpjam_basic_get_setting('favicon')){ 
		echo '<link rel="shortcut icon" href="'.wpjam_basic_get_setting('favicon').'">';
	}
});

add_action('init', function(){
	if(is_multisite() && is_network_admin()){
		return;
	}

	add_action('admin_notices', 'wpjam_topic_admin_notice');
	
	$current	= 3.9;
	$version	= wpjam_basic_get_setting('version') ?: 0;

	if($version >= $current){
		return;
	}

	add_filter('default_option_wpjam-basic', 'wpjam_basic_get_default_settings');
	
	wpjam_basic_update_setting('version', $current);
	
	if($version < 3.9){
		$wpjam_extends	= get_option('wpjam-extends');
		$wpjam_extends	= $wpjam_extends ? array_filter($wpjam_extends) : [];

		if(empty($wpjam_extends)){
			update_option('wpjam-extends', []);
		}

		$wpjam_cdn	= get_option('wpjam-cdn');
		if(empty($wpjam_cdn)){
			update_option('wpjam-cdn', []);
		}
	}

	if($version < 3.72){
		$theme_switched	= get_option('theme_switched', null);
		if(is_null($theme_switched)){
			update_option('theme_switched', '');
		}

		if(is_multisite()){
			$db_locale	= get_option('WPLANG', null);

			if(is_null($db_locale)){
				$db_locale	= get_site_option('WPLANG');
				if($db_locale !== false){
					update_option('WPLANG', $db_locale);
				}
			}
		}
	}
});

function wpjam_topic_admin_notice(){
	if($messages = WPJAM_Verify::get_messages()){
		$unread_count	= $messages['unread_count'] ?? 0;

		if($unread_count){
			echo '<div class="updated"><p>你发布的帖子有<strong>'.$unread_count.'</strong>条回复了，请<a href="'.admin_url('admin.php?page=wpjam-basic-topics&tab=message').'">点击查看</a>！</p></div>';
		}
	}
}

