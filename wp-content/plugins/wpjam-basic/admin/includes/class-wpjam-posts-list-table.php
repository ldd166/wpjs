<?php 
if(!class_exists('WPJAM_List_Table')){
	include WPJAM_BASIC_PLUGIN_DIR.'admin/includes/class-wpjam-list-table.php';
}

class WPJAM_Posts_List_Table extends WPJAM_List_Table{
	public function __construct($args = []){
		$args	= wp_parse_args($args, [
			'model'			=> '',
			'ajax'			=> true,
			'actions'		=> [],
			'search_metas'	=> []
		]);

		$this->set_model($args['model']);

		$current_screen	= get_current_screen();

		$screen_id	= $current_screen->id;
		$post_type	= $args['post_type'] = $screen_id == 'upload' ? 'attachment' : $current_screen->post_type;
		$pt_obj		= get_post_type_object($post_type);

		$args['title']				= $args['title'] ?? $pt_obj->label;
		$args['capability']			= $args['capability'] ?? 'edit_post';
		$args['bulk_capability']	= $args['bulk_capability'] ?? $pt_obj->cap->edit_posts;
		
		$args['actions']	= apply_filters('wpjam_'.$post_type.'_posts_actions', $args['actions'], $post_type);

		$this->_args	= $this->parse_args($args);

		if(wp_doing_ajax()){
			add_action('wp_ajax_wpjam-list-table-action',	[$this, 'ajax_response']);
		}else{
			add_action('admin_head',	[$this, 'admin_head']);
			add_action('admin_footer',	[$this, '_js_vars']);
		}

		if(isset($args['actions']['add'])){
			add_action('wpjam_html_replace',	[$this, 'html_replace']);
		}

		add_action('pre_get_posts', 			[$this, 'pre_get_posts']);

		add_filter('bulk_actions-'.$screen_id,	[$this, 'posts_bulk_actions']);
		
		if($post_type == 'attachment'){
			add_filter('media_row_actions',		[$this, 'post_row_actions'],1,2);

			add_filter('manage_media_columns',			[$this, 'manage_posts_columns']);
			add_filter('manage_media_custom_column',	[$this, 'manage_posts_custom_column', 10, 2]);
		}else{
			if(is_post_type_hierarchical($post_type)){
				add_filter('page_row_actions',	[$this, 'post_row_actions'],1,2);
			}else{
				add_filter('post_row_actions',	[$this, 'post_row_actions'],1,2);
			}

			add_filter('manage_'.$post_type.'_posts_columns',		[$this, 'manage_posts_columns']);
			add_action('manage_'.$post_type.'_posts_custom_column',	[$this, 'manage_posts_custom_column'], 10, 2);
		}

		add_filter('manage_'.$screen_id.'_sortable_columns',	[$this, 'manage_posts_sortable_columns']);
	}

	public function single_row($raw_item){
		global $post, $authordata;

		if(is_numeric($raw_item)){
			$post	= get_post($raw_item);
		}else{
			$post	= $raw_item;	
		}
		
		$authordata = get_userdata($post->post_author);
		$post_type	= $post->post_type;

		if($post_type == 'attachment'){
			$wp_list_table = _get_list_table('WP_Media_List_Table', ['screen'=>get_current_screen()]);

			$post_owner = ( get_current_user_id() == $post->post_author ) ? 'self' : 'other';
			?>
			<tr id="post-<?php echo $post->ID; ?>" class="<?php echo trim( ' author-' . $post_owner . ' status-' . $post->post_status ); ?>">
				<?php $wp_list_table->single_row_columns($post); ?>
			</tr>
			<?php
		}else{
			$wp_list_table = _get_list_table('WP_Posts_List_Table', ['screen'=>get_current_screen()]);
			$wp_list_table->single_row($post);
		}
	}

	public function post_row_actions($row_actions, $post){
		if($post->post_status == 'trash'){
			$row_actions['post_id'] = 'ID: '.$post->ID;
			return $row_actions;
		}

		if($this->_args['actions']){
			$row_actions	= array_merge($row_actions, $this->get_row_actions($this->_args['actions'], $post->ID, $post));
		}

		if(isset($row_actions['trash'])){
			$trash	= $row_actions['trash'];
			unset($row_actions['trash']);

			$row_actions['trash']	= $trash;
		}

		$row_actions['post_id'] = 'ID: '.$post->ID;

		return $row_actions;
	}

	public function posts_bulk_actions($bulk_actions=[]){
		return array_merge($bulk_actions, $this->_args['bulk_actions']);
	}

	public function manage_posts_columns($columns){
		if($this->_args['columns']){
			wpjam_array_push($columns, $this->_args['columns'], 'date'); 
		}

		return $columns;
	}

	public function manage_posts_custom_column($column_name, $post_id){
		if(metadata_exists('post', $post_id, $column_name)){
			$column_value	= get_post_meta($post_id, $column_name, true);	
		}else{
			$column_value	= null;
		}

		echo $this->column_callback($column_value, $column_name, $post_id) ?? '';
	}

	public function manage_posts_sortable_columns($columns){
		return array_merge($columns, $this->_args['sortable_columns']);
	}

	public function html_replace($html){
		$add_button	= wpjam_get_list_table_row_action('add', ['class'=>'page-title-action']);
		return preg_replace('/<a href=".*?" class="page-title-action">.*?<\/a>/i', $add_button, $html);
	}

	public function pre_get_posts($wp_query){
		if($sortable_columns = $this->_args['sortable_columns']){
			$orderby	= $wp_query->get('orderby');

			if($orderby && is_string($orderby) && isset($sortable_columns[$orderby])){
				$fields	= $this->get_fields();
				$field	= $fields[$orderby] ?? '';

				$orderby_type = $field['sortable_column'] ?? 'meta_value';

				if(in_array($orderby_type, ['meta_value_num', 'meta_value'])){
					$wp_query->set('meta_key', $orderby);
					$wp_query->set('orderby', $orderby_type);
				}else{
					$wp_query->set('orderby', $orderby);
				}
			}
		}
	}

	public function admin_head(){
		if($bulk_actions = $this->_args['bulk_actions']){
		?>

		<script type="text/javascript">
		jQuery(function($){
			<?php 
			foreach($bulk_actions as $action_key => $bulk_action) { 
				$bulk_action	= $this->_args['actions'][$action_key];

				$datas	= ['action'=>$action_key, 'bulk'=>true];

				$datas['page_title']	= $bulk_action['page_title']??$bulk_action['title']; 
				$datas['nonce']			= $this->create_nonce('bulk_'.$action_key); 

				if(!empty($bulk_action['direct'])){
					$datas['direct']	= true;
				}

				if(!empty($bulk_action['confirm'])){
					$datas['confirm']	= true;
				}

				echo '$(\'.bulkactions option[value='.$action_key.']\').data('.wpjam_json_encode($datas).')'."\n";
			} 
			?>
		});
		</script>

		<?php }
	}
}