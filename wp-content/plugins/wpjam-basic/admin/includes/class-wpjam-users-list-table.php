<?php 
if(!class_exists('WPJAM_List_Table')){
	include WPJAM_BASIC_PLUGIN_DIR.'admin/includes/class-wpjam-list-table.php';
}

class WPJAM_Users_List_Table extends WPJAM_List_Table{
	public function __construct($args = []){
		$current_screen	= get_current_screen();

		$screen_id	= $current_screen->id;
		
		$args['title']				= $args['title'] ?? '用户';
		$args['capability']			= $args['capability'] ?? 'manage_options';
		$args['bulk_capability']	= $args['bulk_capability'] ?? 'edit_users';

		$args['actions']			= apply_filters('wpjam_users_actions', []);

		$this->_args	= $this->parse_args($args);

		if(wp_doing_ajax()){
			add_action('wp_ajax_wpjam-list-table-action', [$this, 'ajax_response']);
		}else{
			add_action('admin_footer',	[$this, '_js_vars']);
		}

		add_filter('user_row_actions',	[$this, 'user_row_actions'], 1, 2);
		
		add_filter('manage_users_columns',			[$this, 'manage_users_columns']);
		add_filter('manage_users_custom_column',	[$this, 'manage_users_custom_column'],10,3);
		add_filter('manage_users_sortable_columns',	[$this, 'manage_users_sortable_columns']);
	}

	public function single_row($raw_item){
		$wp_list_table = _get_list_table('WP_Users_List_Table', ['screen'=>get_current_screen()]);

		echo $wp_list_table->single_row($raw_item);
	}

	public function user_row_actions($row_actions, $user){
		if($this->_args['actions']){
			$row_actions	= array_merge($row_actions, $this->get_row_actions($this->_args['actions'], $user->ID, $user));
		}

		$row_actions['user_id'] = 'ID: '.$user->ID;	
		
		return $row_actions;
	}

	public function manage_users_columns($columns){
		if($this->_args['columns']){
			wpjam_array_push($columns, $this->_args['columns'], 'posts'); 
		}

		return $columns;
	}

	public function manage_users_custom_column($value, $column_name, $user_id){
		if(metadata_exists('user', $user_id, $column_name)){
			$column_value = get_user_meta($user_id, $column_name, true);
		}else{
			$column_value = null;
		}

		return $this->column_callback($column_value, $column_name, $user_id) ?? $value;
	}

	public function manage_users_sortable_columns($columns){
		return array_merge($columns, $this->_args['sortable_columns']);
	}
}