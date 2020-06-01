<?php
function wpjam_admin_init(){
	global $pagenow, $plugin_page, $plugin_page_setting, $current_admin_url;

	$plugin_page_setting	= null;
	$current_page_hook		= null;

	if(wp_doing_ajax()){
		if(isset($_POST['plugin_page'])){
			$plugin_page	= $_POST['plugin_page'];
		}
		
		$add_menus	= false;
	}elseif($pagenow == 'options.php'){
		$referer_origin	= parse_url(wpjam_get_referer());

		if(empty($referer_origin['query']))	{
			return;
		}

		$referer_args	= wp_parse_args($referer_origin['query']);
		$plugin_page	= $referer_args['page'] ?? '';	// 为了实现多个页面使用通过 option 存储。

		$add_menus	= false;
	}else{
		$add_menus	= true;
	}

	if(!empty($plugin_page) || $add_menus){
		// 获取后台菜单
		if(is_multisite() && is_network_admin()){
			$wpjam_pages	=  apply_filters('wpjam_network_pages', []);

			if(!$wpjam_pages) {
				return;
			}

			$builtin_parent_pages	= [
				'settings'	=> 'settings.php',
				'theme'		=> 'themes.php',
				'themes'	=> 'themes.php',
				'plugins'	=> 'plugins.php',
				'users'		=> 'users.php',
				'sites'		=> 'sites.php',
			];
		}else{
			$wpjam_pages	= WPJAM_Core::get_menu_pages();

			if(!$wpjam_pages){
				return;
			}

			$builtin_parent_pages	= [
				'management'=> 'tools.php',
				'options'	=> 'options-general.php',
				'theme'		=> 'themes.php',
				'themes'	=> 'themes.php',
				'plugins'	=> 'plugins.php',
				'posts'		=> 'edit.php',
				'media'		=> 'upload.php',
				'links'		=> 'link-manager.php',
				'pages'		=> 'edit.php?post_type=page',
				'comments'	=> 'edit-comments.php',
				'users'		=> current_user_can('edit_users')?'users.php':'profile.php',
			];
			
			if($custom_post_types = get_post_types(['_builtin' => false, 'show_ui' => true])){
				foreach ($custom_post_types as $custom_post_type) {
					$builtin_parent_pages[$custom_post_type.'s'] = 'edit.php?post_type='.$custom_post_type;
				}
			}
		}

		foreach ($wpjam_pages as $menu_slug=>$wpjam_page) {
			if(isset($builtin_parent_pages[$menu_slug])){
				$parent_slug = $builtin_parent_pages[$menu_slug];
			}else{
				if(empty($wpjam_page['menu_title'])){
					continue;
				}
				
				$menu_title	= $wpjam_page['menu_title'];
				$page_title	= $wpjam_page['page_title'] = $wpjam_page['page_title']?? $menu_title;

				if($plugin_page == $menu_slug){
					$plugin_page_setting	= $wpjam_page;

					$current_admin_url	= 'admin.php?page='.$plugin_page;
					$current_admin_url	= is_network_admin() ? network_admin_url($current_admin_url) : admin_url($current_admin_url);
				}

				if($add_menus){
					$capability	= $wpjam_page['capability'] ?? 'manage_options';
					$icon		= $wpjam_page['icon'] ?? '';
					$position	= $wpjam_page['position'] ?? '';

					$page_hook	= add_menu_page($page_title, $menu_title, $capability, $menu_slug, 'wpjam_admin_page', $icon, $position);

					if($plugin_page == $menu_slug){
						$current_page_hook	= $page_hook;
					}
				}

				$parent_slug	= $menu_slug;
			}

			if(!empty($wpjam_page['subs'])){
				foreach ($wpjam_page['subs'] as $menu_slug => $wpjam_page) {
					$menu_title	= $wpjam_page['menu_title'] ?? '';
					$page_title	= $wpjam_page['page_title'] = $wpjam_page['page_title'] ?? $menu_title;

					if($plugin_page == $menu_slug){
						$plugin_page_setting	= $wpjam_page;

						if(in_array($parent_slug, $builtin_parent_pages)){
							$current_admin_url	= $parent_slug;
							$current_admin_url 	.= strpos($current_admin_url, '?') ? '&page='.$plugin_page : '?page='.$plugin_page;
						}else{
							$current_admin_url	= 'admin.php?page='.$plugin_page;
						}

						$current_admin_url	= is_network_admin() ? network_admin_url($current_admin_url) : admin_url($current_admin_url);

						if(!$add_menus){
							break;
						}
					}

					if($add_menus){
						$capability	= $wpjam_page['capability'] ?? 'manage_options';
						$page_hook	= add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, 'wpjam_admin_page');
						
						if($plugin_page == $menu_slug){
							$current_page_hook	= $page_hook;
						}
					}
				}	
			}

			if(!$add_menus && $plugin_page_setting){
				break;
			}
		}

		if($plugin_page_setting && $current_page_hook){
			$plugin_page_setting['page_hook']	= $current_page_hook;
		}
	}

	if(wp_doing_ajax()){
		if(isset($_POST['screen_id'])){
			set_current_screen($_POST['screen_id']);
		}elseif(isset($_POST['screen'])){
			set_current_screen($_POST['screen']);	
		}else{
			$ajax_action	= $_POST['action'] ?? '';

			if($ajax_action == 'inline-save-tax'){
				set_current_screen('edit-'.sanitize_key($_POST['taxonomy']));
			}elseif($ajax_action == 'get-comments'){
				set_current_screen('edit-comments');
			}
		}
	}elseif($pagenow == 'options.php'){
		set_current_screen($_POST['screen_id']);
	}
}

function wpjam_admin_load($current_screen=null){	
	global $plugin_page;

	if(!empty($plugin_page)){
		global $plugin_page_setting;

		if($plugin_page_setting){
			wpjam_admin_plugin_page_load($plugin_page_setting);
		}
	}else{
		wpjam_admin_builtin_page_load($current_screen);
	}
}

function wpjam_admin_plugin_page_load($page_setting, $in_tab=false){
	if($in_tab){
		if(!empty($page_setting['tab_file'])){
			include $page_setting['tab_file'];
		}
	}else{
		if(!empty($page_setting['page_file'])){
			include $page_setting['page_file'];
		}
	}

	global $pagenow, $plugin_page, $current_admin_url, $current_query_data, $current_tab, $current_option, $current_list_table, $current_dashboard;

	$current_query_data	= $current_query_data ?? [];

	if($query_args	= $page_setting['query_args'] ?? []){
		foreach($query_args as $query_arg) {
			$current_query_data[$query_arg]	= wpjam_get_data_parameter($query_arg);
		}

		$current_admin_url	= add_query_arg($current_query_data, $current_admin_url);
	}

	do_action('wpjam_plugin_page_load', $plugin_page, $current_tab);

	$function	= $page_setting['function'] ?? null;

	$wp_error	= null;

	if($function == 'tab'){
		$tabs	= $page_setting['tabs'] ?? [];
		$tabs	= apply_filters(wpjam_get_filter_name($plugin_page, 'tabs'), $tabs);

		if($tabs){
			$tabs	= array_combine(array_map('sanitize_key', array_keys($tabs)), array_values($tabs));

			if(wp_doing_ajax() || $pagenow == 'options.php'){
				$current_tab	= wpjam_get_parameter('current_tab', ['method'=>'POST', 'sanitize_callback'=>'sanitize_key']);
			}else{
				$current_tab	= wpjam_get_parameter('tab', ['sanitize_callback'=>'sanitize_key']);

				if(empty($current_tab)){
					$tab_keys		= array_keys($tabs);
					$current_tab	= $tab_keys[0];	
				}
			}
			
			if(empty($current_tab) || empty($tabs[$current_tab])){
				$wp_error	= new WP_Error('invalid_tab', 'Tabs 非法 Tab');
			}
		}else{
			$wp_error	= new WP_Error('empty_tabs', 'Tabs 未设置');
		}

		if($wp_error){
			if(wp_doing_ajax()){
				wpjam_send_json($wp_error);
			}else{
				wp_die($wp_error);
			}
		}

		global $plugin_page_setting;

		$plugin_page_setting['tabs']	= $tabs;
		$plugin_page_setting['tab_url']	= $current_admin_url;

		$current_admin_url	= $current_admin_url.'&tab='.$current_tab;

		wpjam_admin_plugin_page_load($tabs[$current_tab], true);
	}elseif($function == 'option'){
		$current_option	= $page_setting['option_name'] ?? $plugin_page;
		include WPJAM_BASIC_PLUGIN_DIR.'admin/core/options.php';
	}elseif($function == 'list' || $function == 'list_table'){
		$current_list_table	= $page_setting['list_table_name'] ?? $plugin_page;
		include WPJAM_BASIC_PLUGIN_DIR.'admin/core/list-table.php';
	}elseif($function == 'dashboard'){
		$current_dashboard	= $page_setting['dashboard_name'] ?? $plugin_page;
		include WPJAM_BASIC_PLUGIN_DIR.'admin/core/dashboard.php';
	}
}

function wpjam_admin_builtin_page_load($current_screen){
	global $wpjam_list_table;

	$screen_base	= $current_screen->base;

	do_action('wpjam_builtin_page_load', $screen_base, $current_screen);

	if($screen_base == 'dashboard'){
		include WPJAM_BASIC_PLUGIN_DIR.'admin/core/dashboard.php';	
	}elseif($screen_base == 'post'){
		$post_type	= $current_screen->post_type ?? '';

		if($post_type && get_post_type_object($post_type)){
			do_action('wpjam_post_page_file', $post_type);
		}
	}elseif($screen_base == 'edit' || $screen_base == 'upload'){
		$post_type	= $screen_base == 'upload' ? 'attachment' : $current_screen->post_type ?? '';

		if($post_type && get_post_type_object($post_type)){
			include WPJAM_BASIC_PLUGIN_DIR.'admin/includes/class-wpjam-posts-list-table.php';
			
			do_action('wpjam_post_list_page_file', $post_type);

			$wpjam_list_table	= new WPJAM_Posts_List_Table();
		}
	}elseif($screen_base == 'term' || $screen_base == 'edit-tags') {
		$taxonomy	= $current_screen->taxonomy ?? '';

		if($taxonomy && get_taxonomy($taxonomy)){
			include WPJAM_BASIC_PLUGIN_DIR.'admin/includes/class-wpjam-terms-list-table.php';

			do_action('wpjam_term_list_page_file', $taxonomy);

			$wpjam_list_table	= new WPJAM_Terms_List_Table();
		}
	}elseif($screen_base == 'users'){
		include WPJAM_BASIC_PLUGIN_DIR.'admin/includes/class-wpjam-users-list-table.php';
		$wpjam_list_table	= new WPJAM_Users_List_Table();
	}elseif($screen_base == 'edit-comments'){
		
	}

	if($summary = apply_filters('wpjam_builtin_page_summary', '', $current_screen)){
		add_filter('wpjam_html_replace', function($html) use($summary) {
			return str_replace('<hr class="wp-header-end">', '<hr class="wp-header-end">'.wpautop($summary), $html);
		});
	}
}

add_action('wp_loaded', function(){	// 内部的 hook 使用 优先级 9，因为内嵌的 hook 优先级要低
	global $pagenow;

	if($pagenow == 'options.php'){
		// 为了实现多个页面使用通过 option 存储。
		// 注册设置选项，选用的是：'admin_action_' . $_REQUEST['action'] hook，
		// 因为在这之前的 admin_init 检测 $plugin_page 的合法性
		add_action('admin_action_update',	'wpjam_admin_init', 9);
	}elseif(wp_doing_ajax()){
		add_action('admin_init',			'wpjam_admin_init', 9);
	}else{
		add_action('network_admin_menu',	'wpjam_admin_init',	9);
		add_action('admin_menu', 			'wpjam_admin_init',	9);
	}
			
	add_action('current_screen',	'wpjam_admin_load', 9);
});