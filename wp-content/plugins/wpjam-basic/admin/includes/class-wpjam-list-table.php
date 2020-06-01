<?php
if(!class_exists('WP_List_Table')){
	include ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPJAM_List_Table extends WP_List_Table {
	protected $model = '';

	protected $_left_pagination_args = [];

	public function __construct($args = []){
		$args	= wp_parse_args($args, [
			'title'				=> '',
			'screen'			=> '',
			'plural'			=> '',
			'singular'			=> '',
			'model'				=> '',
			'primary_key'		=> '',
			'primary_column'	=> '',
			'query_data'		=> [], // 额外参数
			'capability'		=> 'manage_options',
			'per_page'			=> 50,
			'ajax'				=> true,
			'layout'			=> 1,
			'sortable'			=> false,
			// 'modes'			=> '',
		]);

		$this->_args	= $args;

		$this->set_model($args['model']);

		$model	= $this->get_model();

		if(method_exists($model,'get_primary_key')){
			$args['primary_key']	= $args['model']::get_primary_key();	
		}

		if(method_exists($model, 'get_actions')){
			$args['actions']	= $model::get_actions();
		}else{
			$args['actions']	= $args['actions'] ?? [
				'add'		=> ['title'=>'新建'],
				'edit'		=> ['title'=>'编辑'],
				'duplicate'	=> ['title'=>'复制'],
				'delete'	=> ['title'=>'删除',	'direct'=>true,	'bulk'=>true, 'confirm'=>true],
			];
		}

		$args['actions']	= apply_filters(wpjam_get_filter_name($args['plural'], 'actions'), $args['actions']);
		
		$args	= $this->parse_args($args);

		if(!empty($args['bulk_actions'])){
			$args['columns'] = array_merge(['cb'=>'checkbox'], $args['columns']);
		}

		global $current_query_data;

		if(!empty($current_query_data)){
			$args['query_data']	= array_merge($current_query_data, $args['query_data']);
		}

		if(is_array($args['per_page'])){
			add_screen_option('per_page', $args['per_page']);	// 选项
		}

		if(!empty($args['style'])){
			add_action('admin_enqueue_scripts', function(){
				wp_add_inline_style('list-tables', $this->_args['style']);
			});
		}

		if(method_exists($model, 'admin_head')){
			add_action('admin_head', [$model, 'admin_head']);
		}

		parent::__construct($args);
	}

	public function parse_args($args){
		$screen_base	= get_current_screen()->base;

		$args['bulk_capability']	= $args['bulk_capability'] ?? $args['capability'];

		$args['bulk_actions']		= [];

		if($args['actions']){
			foreach ($args['actions'] as $action_key => $action) {
				if(empty($action['bulk'])) {
					continue;
				}

				$capability	= $action['bulk_capability'] ?? ($action['capability'] ?? $args['bulk_capability']);

				if(current_user_can($capability, 0, $action_key)){
					$args['bulk_actions'][$action_key]	= $action['title'];
				}
			}
		}

		if(!empty($args['sortable'])){
			$args['actions']	= array_merge($args['actions'],[
				'move'	=> ['direct'=>true, 'title'=>'<span class="dashicons dashicons-move"></span>',			'page_title'=>'拖动'],
				'up'	=> ['direct'=>true, 'title'=>'<span class="dashicons dashicons-arrow-up-alt"></span>',	'page_title'=>'向上移动'],
				'down'	=> ['direct'=>true, 'title'=>'<span class="dashicons dashicons-arrow-down-alt"></span>','page_title'=>'向下移动'],
			]);
		}

		$args['fields']				= $this->get_fields();
		$args['flat_fields']		= [];
		$args['columns']			= $args['columns'] ?? [];
		$args['sortable_columns']	= $args['sortable_columns'] ?? [];

		if($fields = $args['fields']){
			foreach($fields as $key => $field){
				if($field['type'] == 'fieldset'){
					$fieldset_type	= $field['fieldset_type'] ?? 'single';

					if($fieldset_type == 'single'){
						foreach($field['fields'] as $sub_key => $sub_field){
							$args['flat_fields'][$sub_key]	= $sub_field;	
						}
					}else{
						$args['flat_fields'][$key]	= $field;
					}	
				}else{
					$args['flat_fields'][$key]	= $field;	
				}
			}

			foreach($args['flat_fields'] as $key => $field){
				if(!in_array($screen_base, ['edit', 'upload', 'term', 'edit-tags', 'users'])){
					if(empty($field['show_admin_column'])) {
						continue;
					}
				}

				$args['columns'][$key] = $field['column_title'] ?? $field['title'];

				if(!empty($field['sortable_column'])){
					$args['sortable_columns'][$key] = [$key, true];
				}
			}
		}

		return $args;
	}

	public function get_model(){
		return $this->model;
	}

	public function set_model($model){
		if($model && class_exists($model)){
			$this->model	= $model; 
		}
	}

	public function set_summary($summary){
		if($summary && empty($this->_args['summary'])){
			$this->_args['summary']	= $summary; 
		}
	}

	public function get_layout(){
		return $this->_args['layout'];
	}

	public function get_summary(){
		return $this->_args['summary']	?? '';
	}

	public function get_action($key){
		$actions	= $this->_args['actions'];

		if($actions && isset($actions[$key])){
			$action	= $actions[$key];

			$action['key']	= $key;

			if(!empty($action['overall'])){
				$action['response']	= 'list';
			}

			return $action;
		}else{
			return [];
		}
	}

	protected function create_nonce($key, $id=''){
		return wp_create_nonce($this->get_nonce_action($key, $id));
	}

	protected function verify_nonce($nonce, $key, $id=''){
		return wp_verify_nonce($nonce, $this->get_nonce_action($key, $id));
	}

	protected function get_nonce_action($key, $id=0){
		$screen_base	= get_current_screen()->base;

		if(in_array($screen_base, ['edit', 'upload', 'term', 'edit-tags', 'users'])){
			$nonce_action	= $key.'-'.$screen_base.'-list-action';
		}else{
			$nonce_action	= $key.'-'.$this->_args['singular'];	
		}
		
		return $id ? $nonce_action.'-'.$id : $nonce_action;
	}

	protected function get_row_actions($actions, $id, $item=[]){
		$screen_base	= get_current_screen()->base;
		$row_actions	= [];
		$next_actions	= [];

		foreach ($actions as $action) {
			if(!empty($action['next'])){
				$next_actions[]	= $action['next'];
			}
		}

		foreach ($actions as $action_key => $action){
			if($action_key == 'add' || !empty($action['overall']) || in_array($action_key, $next_actions)){
				continue;
			}

			if(in_array($screen_base, ['edit', 'upload'])){
				if(isset($action['post_status'])){
					$post_statuses	= is_array($action['post_status']) ? $action['post_status'] : [$action['post_status']];
					
					if(!in_array($item->post_status, $post_statuses)){
						continue;
					}
				}
			}elseif($screen_base == 'edit-tags'){
				if(isset($action['parent'])){
					if($item->parent != $action['parent']){
						continue;
					}
				}
			}elseif($screen_base == 'users'){
				if(isset($action['roles'])){
					if(!array_intersect($item->roles, $action['roles'])){
						continue;
					}
				}
			}

			$action['key']	= $action_key;

			if(!empty($action['filter'])){
				$data			= $action['data'] ?? [];
				if(is_array($action['filter'])){
					$filter_keys	= $action['filter'];
				}else{
					$filter_keys	= explode(',', $filter_keys);
				}

				foreach($filter_keys as $filter_key){
					if(isset($item[$filter_key])){
						$data[$filter_key]	= $item[$filter_key];	
					}
				}

				$action['data']	= $data;
			}

			if($row_action = $this->get_row_action($action, ['id'=>$id])){
				$row_actions[$action_key] = $row_action;
			}
		}

		return $row_actions;
	}

	public function get_row_action($action, $args=[]){
		if(is_string($action)){
			$action	= $this->get_action($action);
			if(!$action){
				return '';
			}
		}

		$args	= wp_parse_args($args, ['id'=>0, 'data'=>[], 'class'=>'', 'style'=>'', 'title'=>'', 'tag'=>'a']);

		if($args['id'] || $action['key'] == 'add'){
			$capability	= $action['capability'] ?? $this->_args['capability'];

			if($capability != 'read' && !current_user_can($capability, $args['id'], $action['key'])){
				return '';
			}
		}else{
			$capability	= $action['capability'] ?? $this->_args['bulk_capability'];

			if($capability != 'read' && !current_user_can($capability, '', 'bulk_'.$action['key'])){
				return '';
			}
		}

		$page_title	= $action['page_title'] ?? ($action['title'].$this->_args['title']);
		$page_title	= wp_strip_all_tags($page_title);

		$attr		= 'title="'.esc_attr($page_title).'"';

		if($args['tag'] == 'a'){
			$attr	.= 'href="javascript:;" ';
		}

		if($args['style']){
			$attr	.= ' style="'.$args['style'].'" ';
		}

		if(!empty($action['filter'])){
			$class		= 'list-table-filter';

			$defaults	= $action['data'] ?? [];
			$data		= wp_parse_args($args['data'], $defaults);
			$data_attr	= $data ? 'data-filter=\''.$this->parse_data_filter($data).'\'' : '';
		}else{
			$class		= $action['key'] == 'move' ? 'list-table-move-action' : 'list-table-action';

			$data_attr	= $this->get_action_data_attr($action, $args);
		}

		if($args['class']){
			$class	.= ' '.$args['class'];
		}
		
		$attr		.= ' class="'.$class.'" ';
		$attr		.= ' '.$data_attr;

		$title		= $args['title'] !== '' ? $args['title'] : $action['title'];

		return '<'.$args['tag'].' '.$attr.'>'.$title.'</'.$args['tag'].'>';
	}

	private function get_action_data_attr($action, $args=[]){
		$args	= wp_parse_args($args, ['type'=>'button', 'id'=>0, 'data'=>[], 'bulk'=>false, 'ids'=>[]]);
		$key	= $action['key'];
		
		$datas	= [];
		$attr	= 'data-action="'.$key.'"';

		$defaults	= $action['data'] ?? [];

		if($args['type'] == 'button'){

			if(isset($this->_args['query_data'])){
				$defaults	= array_merge($defaults, $this->_args['query_data']);
			}
			
			$datas['direct']	= $action['direct'] ?? '';
			$datas['confirm']	= $action['confirm'] ?? '';
			$datas['tb_width']	= $action['tb_width'] ?? '';
			$datas['tb_height']	= $action['tb_height'] ?? '';
		}else{	
			$datas['next']		= $action['next'] ?? '';
		}

		$data	= wp_parse_args($args['data'], $defaults);

		$datas['data']	= $data ? http_build_query($data) : '';
		$datas['bulk']	= $args['bulk'];

		if($args['bulk']){
			$datas['nonce']	= $this->create_nonce('bulk_'.$key);
			$datas['ids']	= $args['ids'] ? http_build_query($args['ids']) : '';
		}else{
			$datas['nonce']	= $this->create_nonce($key, $args['id']);
			$datas['id']	= $args['id'];
		}

		foreach ($datas as $data_key=>$data_value) {
			if($data_value || $data_value === 0){
				$attr	.= ' data-'.$data_key.'="'.$data_value.'"';
			}
		}

		return $attr;
	}

	private function parse_data_filter($filters){
		$data_filters	= [];

		foreach ($filters as $name => $value) {
			$data_filters[]	= ['name'=>$name, 'value'=>$value];
		}

		return wpjam_json_encode($data_filters);
	}

	public function get_filter_link($filters, $title, $class=''){
		$title_attr	= esc_attr(wp_strip_all_tags($title, true));

		return '<a href="javascript:;" title="'.$title_attr.'" class="list-table-filter '.$class.'" data-filter=\''.$this->parse_data_filter($filters).'\'>'.$title.'</a>';
	}

	public function get_fields($key='', $id=0, $args=[]){
		if($key){
			$action	= $this->get_action($key);

			if($action && !empty($action['direct'])){
				return[];
			}
		}
		
		$fields	= [];
		$model	= $this->get_model();

		if($model && method_exists($model, 'get_fields')){
			$fields = $model::get_fields($key, $id);

			if(is_wp_error($fields)){
				return $fields;
			}
		}

		$screen_base	= get_current_screen()->base;

		if(in_array($screen_base, ['edit', 'upload'])){
			$post_type	= $screen_base == 'upload' ? 'attachment' : get_current_screen()->post_type;
			$fields		= apply_filters('wpjam_'.$post_type.'_posts_fields', $fields, $key, $id, $post_type);

			if($key && $id && !is_array($id)){
				$pt_obj		= get_post_type_object($post_type);
				$lable		= $pt_obj->label;
				$_post		= get_post($id);

				$fields		= array_merge(['title'=>['title'=>$lable.'标题',	'type'=>'view',	'value'=>$_post->post_title]], $fields);
			}
		}elseif(in_array($screen_base, ['edit-tags','term'])){
			$taxonomy	= get_current_screen()->taxonomy;
			$fields		= apply_filters('wpjam_'.$taxonomy.'_terms_fields', $fields, $key, $id, $taxonomy);

			if($key && $id && !is_array($id)){
				$tax_obj	= get_taxonomy($taxonomy);
				$lable		= $tax_obj->label;
				$_term		= get_term($id, $taxonomy);

				$fields		= array_merge(['title'=>['title'=>$lable,	'type'=>'view',	'value'=>$_term->name]], $fields);
			}
		}elseif(in_array($screen_base, ['users', 'user-edit', 'profile'])){
			$fields		= apply_filters('wpjam_users_fields', $fields, $key, $id);

			if($key && $id && !is_array($id)){
				$_user		= get_userdata($id);
				$fields		= array_merge(['name'=>['title'=>'用户',	'type'=>'view',	'value'=>$_user->display_name]], $fields);
			}
		}else{
			$fields	= apply_filters(wpjam_get_filter_name($this->_args['singular'], 'fields'), $fields, $key, $id);
		}

		if($key){
			if(!empty($args['include_prev'])){
				if(!empty($action['prev'])){
					$prev	= $action['prev'];
					$args['prev_including']	= true;
					$pre_fields	= $this->get_fields($prev, $id, $args);
					$fields		= array_merge($fields, $pre_fields);
				}
			}

			if(empty($args['prev_including'])){
				if(isset($this->_args['query_data'])){
					foreach($this->_args['query_data'] as $data_key => $data_value){
						$fields[$data_key]	= ['title'=>'', 'type'=>'hidden', 'value'=>$data_value];	
					}
				}

				$primary_key	= $this->_args['primary_key'] ?? '';

				if($primary_key && isset($fields[$primary_key]) && !in_array($key, ['add', 'duplicate'])){
					$fields[$primary_key]['type']	= 'view';
				}
			}
		}

		return $fields;
	}

	public function single_row($raw_item){
		$model	= $this->get_model();

		if(!is_array($raw_item) || is_object($raw_item)){
			$raw_item	= $model::get($raw_item);
		}

		if(empty($raw_item)){
			echo '';
			return ;
		}

		$raw_item	= (array)$raw_item;

		if(method_exists($model, 'before_single_row')){
			$model::before_single_row($raw_item);
		}

		$attr	= '';
		$class	= '';

		if($primary_key	= $this->_args['primary_key']){
			$id	= $raw_item[$primary_key];
			$id	= str_replace('.', '-', $id);

			$attr	.= ' data-id="'.$id.'"';
			$attr	.= ' id="'.$this->_args['singular'].'-'.$id.'"';
			$class	.= 'tr-'.$id;
		}

		$item	= $this->parse_item($raw_item);

		if(isset($item['style'])){
			$attr	.= ' style="'.$item['style'].'"';
		}

		if(isset($item['class'])){
			$class	.= ' '.$item['class'];
		}

		$attr	.= ' class="'.$class.'"';

		echo '<tr '.$attr.'>';
		
		$this->single_row_columns($item);
		
		echo '</tr>';

		if(method_exists($model, 'after_single_row')){
			$model::after_single_row($item, $raw_item);
		}
	}

	protected function parse_item($raw_item){
		$item	= (array)$raw_item;
		$model	= $this->get_model();

		$actions		= $this->_args['actions'];
		$primary_key	= $this->_args['primary_key'];

		if(method_exists($model, 'row_actions')){
			$actions	= $model::row_actions($actions, $item);
		}
		
		if($primary_key && $actions){
			$item_id		= $item[$primary_key];
			$row_actions	= $this->get_row_actions($actions, $item_id, $item);

			if($primary_key == 'id'){
				$row_actions[$primary_key]	= 'ID：'.$item_id;	// 显示 id
			}

			$item['row_actions']	= apply_filters(wpjam_get_filter_name($this->_args['singular'], 'row_actions'), $row_actions, $raw_item);
		}

		if(method_exists($model, 'item_callback')){
			$item = $model::item_callback($item);	
		}

		return apply_filters(wpjam_get_filter_name($this->_args['singular'], 'item'), $item, $raw_item);
	}

	public function column_default($item, $column_name){
		$column_value	= $item[$column_name] ?? null;
		
		if($primary_key = $this->_args['primary_key']){
			return $this->column_callback($column_value, $column_name, $item[$primary_key]) ?? '';	
		}else{
			return $column_value ?? '';
		}
	}

	public function column_cb($item){
		if($primary_key = $this->_args['primary_key']){
			$item_id	= $item[$primary_key];
			if(current_user_can($this->_args['capability'], $item_id)){
				$name	= isset($item['name']) ? strip_tags($item['name']) : $item_id;

				return '<label class="screen-reader-text" for="cb-select-'.esc_attr($item_id).'">选择'.$name.'</label>'.'<input class="list-table-cb" type="checkbox" name="ids[]" value="'.esc_attr($item_id).'" id="cb-select-'.esc_attr($item_id). '" />';
			}else{
				return '<span class="dashicons dashicons-minus"></span>';
			}
		}else{
			return '';	
		}
	}

	protected function is_filterable_column($column_name){
		$model	= $this->get_model();

		if($model && method_exists($model, 'get_filterable_fields')){
			return $model::get_filterable_fields() && in_array($column_name, $model::get_filterable_fields());
		}else{
			return false;
		}
	}

	protected function column_callback($column_value, $column_name, $id){
		$fields	= $this->_args['flat_fields'];
		
		if(empty($fields) || !isset($fields[$column_name])){
			return null;
		}

		$field	= $fields[$column_name];

		if(is_null($column_value)){
			$column_value	= $field['default'] ?? null;	
		}

		if(!empty($field['column_callback'])){
			return call_user_func($field['column_callback'], $id, $column_value);
		}else{
			$options	= $field['options'] ?? [];
			$filterable	= $this->is_filterable_column($column_name);

			if($options){
				if($field['type'] == 'checkbox' && is_array($column_value)){
					$option_values	= [];

					foreach ($column_value as $_column_value) {
						$option_value	= $options[$_column_value] ?? $_column_value;

						if(is_array($option_value)){
							$option_value	= $option_value['title'] ?? '';
						}

						if($filterable){
							$option_value	= $this->get_filter_link([$column_name=>$_column_value], $option_value);
						}

						$option_values[]	= $option_value;
					}

					return implode(',', $option_values);
				}else{
					$option_value	= $options[$column_value] ?? $column_value;

					if(is_array($option_value)){
						$option_value	= $option_value['title'] ?? '';
					}

					if($filterable){
						$option_value =	$this->get_filter_link([$column_name=>$column_value], $option_value);
					}

					return $option_value;
				}
			}else{
				if($filterable){
					$column_value	= $this->get_filter_link([$column_name=>$column_value], $column_value);
				}
				
				return $column_value;
			}
		}
	}

	public function list_table(){
		$this->views();
		
		echo '<form action="#" id="list_table_form" class="list-table-form" method="POST">';

		$this->search_box();
		$this->query_data_input();
		$this->display(); 

		echo '</form>';
	}

	public function col_left(){
		$model 		= $this->get_model();

		if(method_exists($model, 'col_left')){
			$model::col_left();
		}

		echo '<div class="tablenav bottom">';
		
		$this->left_keys_input();
		
		$this->left_pagination();

		echo '</div>';
	}

	public function set_left_pagination_args($args){
		$args = wp_parse_args($args, [
			'total_items'	=> 0,
			'total_pages'	=> 0,
			'per_page'		=> 0,
		]);

		if (!$args['total_pages'] && $args['per_page'] > 0) {
			$args['total_pages']	= ceil($args['total_items']/$args['per_page']);
		}

		$this->_left_pagination_args = $args;
	}

	public function left_pagination(){
		if(empty($this->_left_pagination_args)){
			return;
		}

		$total_items	= $this->_left_pagination_args['total_items'];

		if(empty($total_items)){
			return;
		}

		$total_pages	= $this->_left_pagination_args['total_pages'];
		$current		= wpjam_get_data_parameter('left_paged') ?: 1;

		$disable_prev	= false;
		$disable_next	= false;

		if ( 1 == $current ) {
			$disable_prev	= true;
		}
		
		if ( $total_pages == $current ) {
			$disable_next	= true;
		}

		$page_links	= [];

		if ( $disable_prev ) {
			$page_links[]	= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[]	= sprintf(
				"<a class='prev-page button' href='javascript:;' data-left_paged='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				max( 1, $current - 1 ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}
		
		$html_current_page	= sprintf("<span class='current-page'>%s</span>", $current);
		$html_total_pages	= sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[]		= "<span class='tablenav-paging-text'>".$html_current_page.'/'.$html_total_pages.'</span>';

		if($disable_next){
			$page_links[]	= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[]	= sprintf("<a class='next-page button' href='javascript:;' data-left_paged='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				min( $total_pages, $current + 1 ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if($total_pages > 2){
			$page_links[]	= sprintf(
				"&emsp;<input class='current-page' id='left-current-page' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'><span class='button left-pagination' style='line-height:2; font-size: inherit;'>跳转</span></span>",
				$current,
				strlen( $total_pages )
			);
		}

		$output		= "\n<span class='pagination-links'>" . join("\n", $page_links) . '</span>';
		$page_class = $total_pages < 2 ? ' one-page' : '';

		echo "<div class='tablenav-pages{$page_class}'>$output</div>";
	}

	public function list_page(){
		wpjam_admin_plugin_page_title($this->_args['title'], $this->get_subtitle(), $this->get_summary());

		$layout	= $this->get_layout();
		
		if($layout == 2){
			echo '<div id="col-container" class="wp-clearfix">';

			echo '<div id="col-left">';
			echo '<div class="col-wrap left">';

			$this->col_left();

			echo '</div>';
			echo '</div>';

			echo '<div id="col-right">';
			echo '<div class="list-table col-wrap">';

		}else{
			echo '<div class="list-table">';
		}

		$this->list_table();

		if($layout == 2){
			echo '</div>';
			echo '</div>';	

			echo '</div>';	
		}else{
			echo '</div>';
		}

		return true;
	}

	public function get_subtitle(){
		$model 		= $this->get_model();
		$actions	= $this->_args['actions'];
		$subtitle	= '';

		if(method_exists($model, 'subtitle')){
			$subtitle	.= $model::subtitle();
		}

		if(!empty($_REQUEST['s'])){
			$subtitle 	.= ' “'.esc_html(wp_unslash($_REQUEST['s'])).'”的搜索结果';
		}

		if($subtitle){
			$subtitle	= '<span class="subtitle">'.$subtitle.'</span>';
		}

		if(isset($actions['add'])){
			$subtitle	= $this->get_row_action('add', ['class'=>'page-title-action']).$subtitle;
		}

		return $subtitle;
	}

	public function ajax_response(){
		$action_type	= $_POST['list_action_type'];

		if($action_type == 'list'){
			// if(!$this->verify_nonce($nonce, 'list')){
			// 	wpjam_send_json(['errcode'=>'invalid_nonce', 'errmsg'=>'非法操作']);
			// }

			if($_POST['data']){
				foreach (wp_parse_args($_POST['data']) as $key => $value) {
					$_REQUEST[$key]	= $value;
				}
			}

			$result	= $this->prepare_items();

			if(is_wp_error($result)){
				wpjam_send_json($result);
			}else{
				ob_start();
				$this->list_table();
				$data	= ob_get_clean();
				wpjam_send_json(['errcode'=>0, 'errmsg'=>'', 'data'=>$data, 'type'=>'list']);
			}
		}elseif($action_type == 'left'){
			$result	= $this->prepare_items();

			if(is_wp_error($result)){
				wpjam_send_json($result);
			}else{
				ob_start();
				$this->list_table();
				$data	= ob_get_clean();

				ob_start();
				$this->col_left();
				$left	= ob_get_clean();

				wpjam_send_json(['errcode'=>0, 'errmsg'=>'', 'data'=>$data, 'left'=>$left, 'type'=>'left']);
			}
		}

		$list_action	= $_POST['list_action'];

		if(!$list_action) {
			wpjam_send_json(['errcode'=>'invalid_action', 'errmsg'=>'非法操作']);
		}

		$action	= $this->get_action($list_action);

		if(!$action) {
			wpjam_send_json(['errcode'=>'invalid_action', 'errmsg'=>'非法操作']);
		}

		$id		= $_POST['id'] ?? '';
		$bulk	= $_POST['bulk'] ?? false;
		$ids	= !empty($_POST['ids']) ? wp_parse_args($_POST['ids']) : [];

		$data		= !empty($_POST['data']) ? wp_parse_args($_POST['data']) : [];
		$defaults	= !empty($_POST['defaults']) ? wp_parse_args($_POST['defaults']) : [];
		$data		= wpjam_array_merge($defaults, $data);

		$nonce		= $_POST['_ajax_nonce'] ?? '';

		if($bulk){
			$bulk_action	= 'bulk_'.$list_action;

			if($action_type != 'form'){
				if(!$this->verify_nonce($nonce, $bulk_action)){
					wpjam_send_json(['errcode'=>'invalid_nonce', 'errmsg'=>'非法操作']);
				}
			}
		}else{
			if($action_type != 'form'){
				if(!$this->verify_nonce($nonce, $list_action, $id)){
					wpjam_send_json(['errcode'=>'invalid_nonce',	'errmsg'=>'非法操作']);
				}
			}
		}

		if($id || $list_action == 'add'){
			$capability	= $action['capability'] ?? $this->_args['capability'];

			if(!current_user_can($capability, $id, $list_action)){
				wpjam_send_json(['errcode'=>'bad_authentication', 'errmsg'=>'无权限']);	
			}
		}else{
			$capability	= $action['capability'] ?? $this->_args['bulk_capability'];

			if(!current_user_can($capability, '', 'bulk_'.$list_action)){
				wpjam_send_json(['errcode'=>'bad_authentication', 'errmsg'=>'无权限']);
			}
		}
		
		$response_type	= $action['response'] ?? $list_action;
		$submit_text	= $action['submit_text'] ?? $action['title'];

		$page_title		= $action['page_title'] ?? $action['title'].$this->_args['title'];
		$response		= ['errmsg'=>'', 'page_title'=>$page_title, 'type'=>$response_type, 'bulk'=>$bulk, 'ids'=>$ids, 'id'=>$id];
		$form_args		= compact('action_type', 'response_type', 'bulk', 'ids', 'id');
		
		if($action_type == 'form'){
			$form_args['data']	= $data;
			$ajax_form			= $this->ajax_form($list_action, $form_args);

			if(is_wp_error($ajax_form)){
				wpjam_send_json($ajax_form);
			}

			$response['form']	= $ajax_form;
			wpjam_send_json($response);
		}elseif($action_type == 'direct'){
			if($bulk){
				$result	= $this->list_action($list_action, $ids); 
			}else{
				if(in_array($list_action, ['move', 'up', 'down'])){
					$result	= $this->list_action('move', $id, $data);
				}else{
					$result	= $this->list_action($list_action, $id);
					
					if($list_action == 'duplicate'){
						$id = $result;
					}
				}
			}
		}elseif($action_type == 'submit'){
			if($response_type != 'form'){
				$form_args['data']	= $defaults;

				if($bulk){
					$fields	= $this->get_fields($list_action, $ids, ['include_prev'=>true]);
					$data	= wpjam_validate_fields_value($fields, $data);
					$result	= $this->list_action($list_action, $ids, $data); 
				}else{
					$fields	= $this->get_fields($list_action, $id, ['include_prev'=>true]);
					$data	= wpjam_validate_fields_value($fields, $data);
					$result	= $this->list_action($list_action, $id, $data);
				}

				if(is_wp_error($fields)){
					wpjam_send_json($fields);
				}
			}else{
				$form_args['data']	= $data;

				$result		= null;
			}
		}

		if($result && is_wp_error($result)){
			wpjam_send_json($result);
		}

		if($response_type == 'append'){
			$response['data']	= $result;
			wpjam_send_json($response);
		}elseif($response_type == 'list'){
			$result	= $this->prepare_items();

			if(is_wp_error($result)){
				wpjam_send_json($result);
			}else{
				ob_start();
				$this->list_table();
				$data	= ob_get_clean();
			}
		}elseif(in_array($response_type, ['delete', 'move', 'up', 'down', 'form'])){
			$data ='';
		}elseif(in_array($response_type, ['add', 'duplicate'])){
			$id		= $result;
			$result	= true;

			if($id){
				$response['id']	= $form_args['id'] = $id;
				ob_start();
				$this->single_row($id);
				$data	= ob_get_clean();
			}else{
				$data	= '';
			}
		}else{
			$update_row	= $action['update_row'] ?? true;

			if($bulk){
				$screen_base	= get_current_screen()->base;
				
				if(in_array($screen_base, ['edit','upload'])){
					$items	= WPJAM_Post::get_by_ids($ids);
				}elseif($screen_base == 'edit-tags'){
					$items	= WPJAM_Term::get_by_ids($ids);
				}elseif($screen_base == 'users'){
					// $items	= WPJAM_User::get_by_ids($ids);
				}else{
					$model	= $this->get_model();
					$items	= $model::get_by_ids($ids);	
				}
				
				$data	= [];
				if($update_row){
					foreach ($items as $id => $item) {
						ob_start();
						$this->single_row($item);
						$data[$id]	= ob_get_clean();
					}
				}
			}else{
				$data	= '';
				if($update_row){
					ob_start();
					$this->single_row($id);
					$data	= ob_get_clean();
				}
			}
		}

		$response['data']	= $data;

		if($response_type != 'form'){
			if($result && is_array($result) && !empty($result['errmsg']) && $result['errmsg'] != 'ok'){ // 有些第三方接口返回 errmsg ： ok
				$response['errmsg'] = $result['errmsg'];
			}else{
				$response['errmsg'] = $submit_text.'成功';
			}
		}
		
		if($action_type == 'submit'){
			if(!in_array($response_type, ['delete','list'])){
				if(!empty($action['next'])){
					$response['next_action']= $action['next'];
					$next_action			= $this->get_action($action['next']);
					$response['page_title']	= $next_action['page_title'] ?? $next_action['title'].$this->_args['title'];
					$response['errmsg']		= '';
				}

				$ajax_form	= $this->ajax_form($list_action, $form_args);

				if(is_wp_error($ajax_form)){
					wpjam_send_json($ajax_form);
				}

				$response['form']	= $ajax_form;
			}

			if(in_array($response_type, ['add', 'duplicate'])){
				if(isset($action['last'])){
					$response['last']	= true;	
				}
			}
		}
		
		wpjam_send_json($response);
	}

	public function list_action($list_action='', $id=0, $data=null){
		$bulk	= false;

		if(is_array($id)){
			$ids			= $id;
			$bulk			= true;
			$bulk_action	= 'bulk_'.$list_action;
		}

		$model	= $this->get_model();
		$result	= null;

		if($model){
			if($bulk){
				if(method_exists($model, $bulk_action)){
					if(is_null($data)){
						$result	= $model::$bulk_action($ids);
					}else{
						$result	= $model::$bulk_action($ids, $data);
					}

					$result	= is_null($result) ? true : $result;
				}else{
					if(method_exists($model, $list_action)){
						foreach($ids as $_id) {
							if(is_null($data)){
								$result	= $model::$list_action($_id);
							}else{
								$result	= $model::$list_action($_id, $data);
							}
							
							if(is_wp_error($result)){
								return $result;
							}
						}

						$result	= is_null($result) ? true : $result;
					}
				}
			}else{
				$action			= $this->get_action($list_action);
				$response_type	= $action['response'] ?? $list_action;

				if($list_action == 'add'){
					$list_action	= 'insert';
				}elseif($list_action == 'edit'){
					$list_action	= 'update';
				}elseif($list_action == 'duplicate'){
					if(!is_null($data)){
						$list_action	= 'insert';
					}
				}

				if(method_exists($model, $list_action)){
					if(!empty($action['overall']) || $list_action == 'insert' || $response_type == 'add'){
						if(is_null($data)){
							$result	= $model::$list_action();
						}else{
							$result	= $model::$list_action($data);
						}
					}else{
						if(is_null($data)){
							$result	= $model::$list_action($id);
						}else{
							$result	= $model::$list_action($id, $data);	
						}
					}

					$result	= is_null($result) ? true : $result;
				}
			}
		}

		$screen_base	= get_current_screen()->base;

		if(in_array($screen_base, ['edit','upload'])){
			$page_data_type		= $screen_base == 'upload' ? 'attachment' : get_current_screen()->post_type;
			$list_action_hook	= 'wpjam_'.$page_data_type.'_posts_list_action';
		}elseif($screen_base == 'edit-tags'){
			$page_data_type		= get_current_screen()->taxonomy;
			$list_action_hook	= 'wpjam_'.$page_data_type.'_terms_list_action';
		}elseif($screen_base == 'users'){
			$page_data_type		= 'users';
			$list_action_hook	= 'wpjam_users_list_action';
		}else{
			$page_data_type		= $this->_args['singular'];	
			$list_action_hook	= wpjam_get_filter_name($page_data_type, 'list_action');
		}
		
		$result	= apply_filters($list_action_hook, $result, $list_action, $id, $data, $page_data_type);

		if(is_null($result)){
			return new WP_Error('empty_list_action', '没有定义该操作');
		}

		return $result;
	}

	public function ajax_form($list_action, $args=[]){
		$action	= $this->get_action($list_action);
		$next	= $action['next'] ?? false;

		if($next && $args['action_type'] == 'submit'){
			$prev_action	= $action;
			$list_action	= $next;
			$action			= $this->get_action($next);
		}

		$defaults	= $args['data'];
		$bulk		= $args['bulk'];
		if($bulk){
			$ids	= $args['ids'];
			$fields	= $this->get_fields($list_action, $ids);

			if(is_wp_error($fields)){
				return $fields;
			}
			$data	= $defaults;
			
			$data_attr	= $this->get_action_data_attr($action, ['type'=>'form', 'bulk'=>true, 'ids'=>$ids, 'data'=>$defaults]);
		}else{
			$id		= $args['id'];
			$fields	= $this->get_fields($list_action, $id);

			if(is_wp_error($fields)){
				return $fields;
			}

			$data	= [];
			
			if($id && !in_array(get_current_screen()->base, ['edit', 'upload', 'edit-tags', 'users'])){
				if($args['action_type'] != 'submit' || !$args['response_type'] != 'form'){
					$model	= $this->get_model();
					$data	= $model::get($id);

					if(empty($data) || is_wp_error($data)){
						wpjam_send_json(['errcode'=>'invalid_id', 'errmsg'=>'非法ID']);
					}
				}
			}

			$data_attr	= $this->get_action_data_attr($action, ['type'=>'form', 'id'=>$id, 'data'=>$defaults]);

			$data		= wp_parse_args($data, $defaults);
		}	

		$output	= '';
		$output	.= '<div class="list-table-action-notice notice inline is-dismissible hidden"></div>';
		$output	.= '<form method="post" id="list_table_action_form" action="#" '.$data_attr.'>';
		$output	.= wpjam_fields($fields, ['data'=>$data, 'echo'=>false]);

		$submit_text	= $action['submit_text'] ?? $action['title'];

		if($submit_text || isset($prev_action) || !empty($action['prev'])){
			$output	.= '<p class="submit">';

			if(isset($prev_action)){
				$data_attr	= $this->get_action_data_attr($prev_action, $args);
				$output		.= '<input type="button" class="list-table-action button large" '.$data_attr.' value="返回">&emsp;';
			}elseif(!empty($action['prev'])){
				$data_attr	= $this->get_action_data_attr($this->get_action($action['prev']), $args);
				$output		.= '<input type="button" class="list-table-action button large" '.$data_attr.' value="返回">&emsp;';
			}

			if($submit_text){
				$submit_text	= !empty($action['next']) ? '下一步' : $submit_text;
				$output			.= '<input type="submit" name="list-table-submit" id="list-table-submit" class="button-primary large"  value="'.$submit_text.'"> <span class="spinner"></span>';
			}
			
			$output	.= '</p>';
		}

		$output	.= "</form>";

		if($args['response_type'] == 'append'){ 
			$output	.= '<div class="card response" style="display:none;"></div>'; 
		}

		return $output;
	}

	protected function bulk_actions( $which = '' ) {
		if(is_null($this->_actions)){
			$this->_actions = $this->_args['bulk_actions'];
			$two	= '';
		}else{
			$two	= '2';
		}

		if(empty($this->_actions)){
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $key => $title) {
			
			if($action	= $this->get_action($key)){
				$class		= 'edit' === $key ? ' class="hide-if-no-js"' : '';
				$data_attr	= $this->get_action_data_attr($action, ['bulk'=>true]);

				echo "\t" . '<option value="' . $key . '"' . $class . $data_attr .'">' . $title . "</option>\n";
			}	
		}

		echo "</select>\n";

		submit_button(__('Apply'), 'action list-table-bulk-action', '', false, ['id'=>"doaction$two"]);
		echo "\n";
	}

	protected function get_table_classes() {
		$classes = parent::get_table_classes();

		if(empty($this->_args['fixed'])){
			$classes	= array_diff($classes, ['fixed']);
		}

		return $classes;
	}

	public function get_plural(){
		return $this->_args['plural'];
	}

	public function get_singular(){
		return $this->_args['singular'];
	}

	protected function get_default_primary_column_name(){
		if(!empty($this->_args['primary_column'])){
			return $this->_args['primary_column'];
		}

		return parent::get_default_primary_column_name();
	}

	protected function handle_row_actions($item, $column_name, $primary){
		if ( $primary !== $column_name ) {
			return '';
		}

		if(!empty($item['row_actions'])){
			return $this->row_actions($item['row_actions'], false);
		}
	}

	public function row_actions($actions, $always_visible = true){
		return parent::row_actions($actions, $always_visible);
	}

	public function get_per_page(){
		if($this->_args['per_page'] && is_numeric($this->_args['per_page'])){
			return $this->_args['per_page'];
		}

		$option	= get_current_screen()->get_option('per_page', 'option');
		if($option){
			$default	= get_current_screen()->get_option('per_page', 'default')?:50;
			$per_page	= $this->get_items_per_page($option, $default);

			return $per_page;
		}

		return 50;
	}

	public function get_offset(){
		return ($this->get_pagenum()-1) * $this->get_per_page();
	}

	public function get_limit(){
		return $this->get_offset().','.$this->get_per_page();
	}

	public function prepare_items(){
		if($model = $this->get_model()){
			$model_reflection	= new ReflectionClass($model);
			$model_methods		= $model_reflection->getMethods();
			$model_methods		= wp_list_pluck($model_methods, 'class', 'name');

			if(isset($model_methods['query_items']) && $model_methods['query_items'] == $model){
				$method	= 'query_items';
			}elseif(isset($model_methods['list']) && $model_methods['list'] == $model){
				$method	= 'list';
			}elseif(isset($model_methods['query_items']) && $model_methods['query_items'] != 'WPJAM_Model'){
				$method	= 'query_items';
			}elseif(isset($model_methods['list']) && $model_methods['list'] != 'WPJAM_Model'){
				$method	= 'list';
			}else{
				$method	= 'query_items';
			}

			$result = $model::$method($this->get_per_page(), $this->get_offset());

			if(is_wp_error($result)){
				$this->items	= [];

				return $result;	
			}else{
				$this->items	= $result['items'] ?? [];
				$total_items	= $result['total'] ?? 0;
				if($total_items){
					$this->set_pagination_args( array(
						'total_items'	=> $total_items,
						'per_page'		=> $this->get_per_page()
					));
				}
			}
		}else{
			$args = func_get_args();

			$this->items	= $args[0];
			$this->set_pagination_args( array(
				'total_items'	=> $args[1],
				'per_page'		=> $this->get_per_page()
			));
		}

		return true;
	}

	public function get_columns(){
		return $this->_args['columns'];
	}

	public function get_sortable_columns(){
		return $this->_args['sortable_columns'];
	}

	public function get_views(){
		if($model = $this->get_model()){
			if(method_exists($model, 'views')){
				return $model::views();
			}
		}else{
			if(!empty($this->_args['views'])){
				trigger_error('list_table_args_views');
				return call_user_func($this->_args['views'],[]);
			}
		}

		return [];
	}

	public function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field($this->get_nonce_action('list'));
		}
		?>
		<div class="tablenav <?php echo esc_attr($which);?>">
			<?php if (!empty($this->_args['bulk_actions']) && $this->has_items() ){ ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php } ?>

			<?php $this->extra_tablenav($which); ?>

			<?php $this->pagination($which); ?>

			<br class="clear" />
		</div>
		<?php
	}

	public function extra_tablenav($which='top') {
		$model 		= $this->get_model();

		if(method_exists($model, 'extra_tablenav')){
			$model::extra_tablenav($which);
		}

		if($which == 'top'){
			$actions	= $this->_args['actions'];

			if($actions){
				$overall_actions = '';

				foreach ($actions as $action_key => $action) {
					if(!empty($action['overall'])){
						$action['key']		= $action_key;
						$overall_actions	.= $this->get_row_action($action, ['class'=>'button-primary button']);
					}
				}

				if($overall_actions){
					echo '<div class="alignleft actions overallactions">'.$overall_actions.'</div>';
				}
			}
		}

		do_action(wpjam_get_filter_name($this->_args['plural'], 'extra_tablenav'), $which);	
	}

	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_REQUEST['orderby'] ) ) {
			$current_orderby = $_REQUEST['orderby'];
		} else {
			$current_orderby = '';
		}

		if ( isset( $_REQUEST['order'] ) && 'desc' === $_REQUEST['order'] ) {
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key )
				$class[] = 'check-column';

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			$data_attr	= '';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby === $orderby ) {
					$order = 'asc' === $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$class[] = 'list-table-sort';

				if($this->get_model()){
					$column_display_name = '<a href="javascript:;"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
				}else{
					$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
				}

				$data_attr	= 'data-orderby="'.$orderby.'" data-order="'.$order.'"'; 
			}

			$tag = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<$tag $scope $id $class $data_attr>$column_display_name</$tag>";
		}
	}

	public function query_data_input(){
		if($query_data = array_filter($this->_args['query_data'])){ $data	= wpjam_json_encode($query_data); ?>
			<input type="hidden" id="wpjam_query_data" name="wpjam_query_data" value='<?php echo $data; ?>' />
		<?php }
	}

	public function left_keys_input(){
		if($left_keys = array_filter($this->_args['left_keys'])){ $keys	= wpjam_json_encode($left_keys); ?>
			<input type="hidden" id="wpjam_left_keys" name="wpjam_left_keys" value='<?php echo $keys; ?>' />
		<?php }
	}

	public function search_box($text='搜索', $input_id='wpjam') {
		if($this->is_searchable()){

			$input_id = $input_id . '-search-input';
			?>
			<p class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
				<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>
			<br class="clear" />
			<?php
		}
	}

	public function is_searchable(){
		if(empty($_REQUEST['s']) && (!$this->has_items() || $this->_pagination_args['total_pages'] <= 1)){
			return false;
		}

		if(isset($this->_args['search'])){
			return $this->_args['search'];
		}elseif($model = $this->get_model()){
			return method_exists($model, 'get_searchable_fields') && $model::get_searchable_fields();
		}else{
			return false;
		}
	}

	public function current_action() {
		if(isset($_REQUEST['modal_action'])){
			return $_REQUEST['modal_action'];
		}

		return parent::current_action();
	}

	public function get_current_action_js_args(){
		$current_action	= $this->current_action();

		if(empty($current_action)){
			return false;
		}
		
		$action	= $this->get_action($current_action);

		if(empty($action) || !empty($action['direct'])){
			return false;
		}

		$sanitize_callback	= function($value){
			if($value){
				$value	= wp_parse_args(urldecode($value));
				return array_map('sanitize_textarea_field', $value);
			}else{
				return [];
			}
		};

		$data	= wpjam_get_parameter('data', ['sanitize_callback'=>$sanitize_callback]);

		if(!empty($this->_args['query_data'])){
			$data 	= array_merge($data, $this->_args['query_data']);
		}

		$data	= $data ? http_build_query($data) : null;
		$args	= ['list_action_type'=>'form', 'list_action'=>$current_action, 'data'=>$data];

		if($current_action !='add'){
			if($id	= wpjam_get_parameter('id', ['sanitize_callback'=>'sanitize_text_field'])){
				$args['id']	= $id;
			}
		}

		return $args;
	}

	public function _js_vars() {
		?>

		<script type="text/javascript">
		jQuery(function($){
			if($('.tablenav.top').find('div.alignleft').length == 0){
				$('.tablenav.top').css({clear:'none'});
			}

			<?php if(!empty($this->_args['sortable'])){ 
				$sortable_items	= $this->_args['sortable'] === true ? ' >tr' : $this->_args['sortable']['items'];
				echo "$.wpjam_list_table_sortable('".$sortable_items."');"; 
			} ?>

			<?php if($args = $this->get_current_action_js_args()){ 
				echo "$.wpjam_list_table_action(".wpjam_json_encode($args).");"; 
			} ?>

		});
		</script>

		<?php

		$model	= $this->get_model();

		if($model && method_exists($model, 'admin_footer')){
			$model::admin_footer();
		}
	}
}