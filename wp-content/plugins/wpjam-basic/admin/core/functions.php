<?php
// 后台页面
function wpjam_admin_page(){
	global $plugin_page_setting;

	echo '<div class="wrap">';

	wpjam_admin_plugin_page($plugin_page_setting);

	echo '</div>';
}

// 后台页面
function wpjam_admin_plugin_page($page_setting){
	$function	= $page_setting['function'] ?? null;

	if($function == 'list'){
		wpjam_admin_list_page($page_setting);
	}elseif($function == 'option'){
		wpjam_option_page($page_setting);
	}elseif($function == 'tab'){
		wpjam_admin_tab_page($page_setting);
	}elseif($function == 'dashboard'){
		wpjam_admin_dashboard_page($page_setting);
	}else{
		if(empty($function)){
			global $plugin_page, $current_tab;

			if(empty($current_tab)){
				$function	= wpjam_get_filter_name($plugin_page, 'page');
			}else{
				wp_die('tab 未设置 function');
			}
		}

		if(is_callable($function)){
			wpjam_admin_plugin_page_title($page_setting);

			call_user_func($function);
		}else{
			wp_die($function.'不存在');
		}
	}
}

// Tab 后台页面
function wpjam_admin_tab_page($page_setting){
	global $plugin_page, $current_tab, $current_admin_url;

	$function	= wpjam_get_filter_name($plugin_page, 'page');	// 所有 Tab 页面都执行的函数

	if(count($page_setting['tabs']) > 1){

		$summary	= $page_setting['summary'] ?? '';
		
		wpjam_admin_plugin_page_title($page_setting, '', $summary);
		
		if(is_callable($function)){
			call_user_func($function);
		}

		echo '<nav class="nav-tab-wrapper wp-clearfix">';
		
		foreach ($page_setting['tabs'] as $tab_key => $tab) {
			$class	= 'nav-tab';
		
			if($current_tab == $tab_key){
				$tab_url	= $current_admin_url;
				$class		.= ' nav-tab-active';
			}else{
				$tab_url	= $page_setting['tab_url'].'&tab='.$tab_key;
			}

			echo '<a class="'.$class.'" href="'. $tab_url.'">'.$tab['title'].'</a>';
		}

		echo '</nav>';
	}else{
		if(is_callable($function)){
			call_user_func($function);
		}
	}

	$current_tab_setting	= $page_setting['tabs'][$current_tab];

	wpjam_admin_plugin_page($current_tab_setting);
}

function wpjam_admin_plugin_page_title($page_title, $subtitle='', $summary=null){
	global $current_tab, $plugin_page_setting;

	if(is_array($page_title)){
		$page_setting	= $page_title;
		$page_title		= $page_setting['page_title'] ?? $page_setting['title'];

		if(!empty($page_setting['function']) && $page_setting['function'] == 'tab'){
			$doing_tab_title	= true;
		}

		if(is_null($summary)){
			$summary	= wpjam_admin_plugin_page_summary($page_setting);
		}
	}

	if(empty($doing_tab_title) && $current_tab && count($plugin_page_setting['tabs']) > 1){
		echo '<h2>'.$page_title.$subtitle.'</h2>';
	}else{
		echo '<h1 class="wp-heading-inline">'.$page_title.'</h1>';

		if($subtitle){
			echo $subtitle;	
		}

		echo '<hr class="wp-header-end">';
	}

	if($summary){
		echo wpautop($summary);
	}
}

function wpjam_admin_plugin_page_summary($page_setting=null, $echo=false){
	if($page_setting && !empty($page_setting['summary'])){
		$summary	= $page_setting['summary'];
	}else{
		$summary	= apply_filters('wpjam_plugin_page_summary', '');
	}

	if($echo){
		echo $summary;
	}else{
		return $summary;
	}
}

function wpjam_get_ajax_button($args){
	global $plugin_page;

	$args	= wp_parse_args($args, [
		'action'		=> '',
		'data'			=> [],
		'direct'		=> '',
		'confirm'		=> '',
		'tb_width'		=> 0,
		'tb_height'		=> 0,
		'button_text'	=> '保存',
		'page_title'	=> '',
		'tag'			=> 'a',
		'class'			=> 'button-primary large',
		'style'			=> ''
	]);

	$action	= $args['action'];

	if(empty($action)){
		return '';
	}

	$page_title = $args['page_title'] ?: $args['button_text'];

	$attr	= ' title="'.esc_attr($page_title).'" id="wpjam_button_'.$action.'"';

	if($args['tag'] == 'a'){
		$attr	.= 'href="javascript:;"';
	}

	$datas	= [];

	$datas['action']	= $action;
	$datas['nonce']		= wp_create_nonce($plugin_page.'-'.$action);
	$datas['data']		= $args['data'] ? http_build_query($args['data']) : '';
	$datas['title']		= $page_title;
	
	$datas	+= wp_array_slice_assoc($args, ['direct', 'confirm', 'tb_width', 'tb_height']);
	$datas	= array_filter($datas);

	foreach ($datas as $data_key=>$data_value) {
		$attr	.= ' data-'.$data_key.'="'.$data_value.'"';
	}

	if($args['style']){
		$attr	.= ' style="'.$args['style'].'"';
	}
	
	$class	= 'wpjam-button';
	$class	.= $args['class'] ? ' '.$args['class'] : '';
	$attr	.= ' class="'.$class.'"';
	
	return '<'.$args['tag'].$attr.'>'.$args['button_text'].'</'.$args['tag'].'>';
}

function wpjam_get_ajax_form($args){
	$args	= wp_parse_args($args, [
		'data_type'		=> 'form',
		'fields_type'	=> 'table',
		'fields'		=> [],
		'data'			=> [],
		'bulk'			=> false,
		'ids'			=> [],
		'id'			=> '',
		'action'		=> '',
		'page_title'	=> '',
		'submit_text'	=> '',
		'nonce'			=> '',
		'form_id'		=> 'wpjam_form'
	]);

	$action	= $args['action'];
	$output	= '';

	if($fields = $args['fields']){
		$attr	= ' method="post" action="#"';
		$attr	.= 'id="'.$args['form_id'].'"';

		$datas	= [];

		if($action){
			global $plugin_page;
			
			$datas['action']	= $action;
			$datas['nonce']		= $args['nonce'] ?: wp_create_nonce($plugin_page.'-'.$action);
		}
			
		$datas['title']		= $args['page_title'] ?: $args['submit_text'];

		if($args['bulk']){
			$datas['bulk']	= $args['bulk'];
			$datas['ids']	= $args['ids'] ? http_build_query($args['ids']) : '';
		}else{
			$datas['id']	= $args['id'];
		}

		$datas	= array_filter($datas);

		foreach ($datas as $data_key=>$data_value) {
			$attr	.= ' data-'.$data_key.'="'.$data_value.'"';
		}

		$output	.= '<div class="page-action-notice notice is-dismissible hidden"></div>';
		
		$output	.=  '<form'.$attr.'>';
		
		$args['echo']	= false;
		$output	.= WPJAM_Field::fields_callback($fields, $args);
	}

	if($args['submit_text']){
		$output	.= '<p class="submit"><input type="submit" class="button-primary large" value="'.$args['submit_text'].'"> <span class="spinner"></span></p>';
	}

	$output	.= '<div class="response" style="display:none;"></div>';

	if($fields){
		$output	.= '</form>';
	}

	return $output;
}

function wpjam_ajax_button($args){
	echo wpjam_get_ajax_button($args);
}

function wpjam_ajax_form($args){
	echo wpjam_get_ajax_form($args);
}

// 获取页面来源
function wpjam_get_referer(){
	$referer	= wp_get_original_referer();
	$referer	= $referer?:wp_get_referer();

	$removable_query_args	= array_merge(wp_removable_query_args(), ['_wp_http_referer', 'action', 'action2', '_wpnonce']);

	return remove_query_arg($removable_query_args, $referer);	
}

function wpjam_get_form_post($fields, $nonce_action='', $capability='manage_options'){
	check_admin_referer($nonce_action);

	if( !current_user_can( $capability )){
		ob_clean();
		wp_die('无权限');
	}

	return WPJAM_Field::validate_fields_value($fields);
}

// 自定义主题更新
/* 数据格式：
{
	theme: "Autumn",
	new_version: "2.0.1",
	url: "http://www.xintheme.com/theme/4893.html",
	package: "http://www.xintheme.com/download/Autumn.zip"
}
*/
function wpjam_register_theme_upgrader($upgrader_url){
	add_filter('site_transient_update_themes',  function($transient) use($upgrader_url){
		$theme	= get_template();

		if(empty($transient->checked[$theme])){
			return $transient;
		}
		
		$remote	= get_transient('wpjam_theme_upgrade_'.$theme);

		if(false == $remote){
			$remote = wpjam_remote_request($upgrader_url);
	 
			if(!is_wp_error($remote)){
				set_transient( 'wpjam_theme_upgrade_'.$theme, $remote, HOUR_IN_SECONDS*12 ); // 12 hours cache
			}
		}

		if($remote && !is_wp_error($remote)){
			if(version_compare( $transient->checked[$theme], $remote['new_version'], '<' )){
				$transient->response[$theme]	= $remote;
			}
		}

		return $transient;
	});
}


function wpjam_get_list_table_filter_link($filters, $title, $class=''){
	global $wpjam_list_table;
	return $wpjam_list_table->get_filter_link($filters, $title, $class);
}

function wpjam_get_list_table_row_action($action, $args=[]){
	global $wpjam_list_table;
	return $wpjam_list_table->get_row_action($action, $args);
}