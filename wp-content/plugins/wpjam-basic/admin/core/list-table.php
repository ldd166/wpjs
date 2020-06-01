<?php
include WPJAM_BASIC_PLUGIN_DIR.'admin/includes/class-wpjam-list-table.php';

if(wp_doing_ajax()){
	add_action('wp_ajax_wpjam-list-table-action', function(){
		global $current_list_table, $wpjam_list_table;

		$wpjam_list_table	= wpjam_get_list_table($current_list_table);
		if(is_wp_error($wpjam_list_table)){
			wpjam_send_json($wpjam_list_table);
		}else{
			$wpjam_list_table->ajax_response();
		}
	});	
}else{
	global $plugin_page_setting;

	if(isset($plugin_page_setting['page_hook'])){
		add_action('load-'.$plugin_page_setting['page_hook'], function(){
			global $current_list_table, $wpjam_list_table;

			$wpjam_list_table	= wpjam_get_list_table($current_list_table);
			
			if(is_wp_error($wpjam_list_table)){
				wp_die($wpjam_list_table->get_error_message());
			}
		});
	}
}

add_action('admin_notices', function(){
	global $wpjam_list_table;

	$result = $wpjam_list_table->prepare_items();

	if(is_wp_error($result)){
		echo '<div class="list-table-notice notice notice-error is-dismissible"><p>'.$result->get_error_message().'</p></div>';
	}else{
		echo '<div class="list-table-notice notice is-dismissible hidden"></div>';
	}
});

function wpjam_get_list_table($current_list_table){

	$wpjam_list_table_args	= apply_filters(wpjam_get_filter_name($current_list_table, 'list_table'), []);

	if(empty($wpjam_list_table_args)){
		return new WP_Error('invalid_list_table_args', '非法 List Table 参数');
	}

	$wpjam_list_table_args	= wp_parse_args($wpjam_list_table_args, ['primary_key'=>'id', 'name'=>$current_list_table, 'screen'=>get_current_screen(), 'model'=>'']);

	$model	= $wpjam_list_table_args['model']; 

	if(empty($model) || !class_exists($model)){
		return new WP_Error('invalid_model', 'List Table 的 Model 未定义或不存在');
	}

	return new WPJAM_List_Table($wpjam_list_table_args);
}

function wpjam_admin_list_page($page_setting=[]){
	global $wpjam_list_table;

	if($wpjam_list_table){
		if($summary = wpjam_admin_plugin_page_summary($page_setting)){
			$wpjam_list_table->set_summary($summary);	
		}

		$wpjam_list_table->list_page();
	}
}

// add_action('current_screen', function($current_screen){
// 	global $plugin_page;
// 	// 如果是通过 wpjam_pages filter 定义的后台菜单
// 	// 需要设置 $current_screen->id=$plugin_page
// 	// 否则隐藏列功能就会出问题。
// 	$current_screen->id	= $current_screen->base = $plugin_page;
// });


