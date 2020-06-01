<?php
add_filter('term_updated_messages', function($messages){
	global $taxonomy;

	if($taxonomy == 'post_tag' || $taxonomy == 'category'){
		return $messages;
	}

	$labels		= get_taxonomy_labels(get_taxonomy($taxonomy));
	$label_name	= $labels->name;

	$messages[$taxonomy]	= array_map(function($message) use ($label_name){
		if($message == $label_name) return $message;

		return str_replace(
			['项目', 'Item'], 
			[$label_name, ucfirst($label_name)], 
			$message
		);
	}, $messages['_item']);

	return $messages;
});

add_filter('taxonomy_parent_dropdown_args', function($args, $taxonomy, $action_type){
	$tax_obj	= get_taxonomy($taxonomy);
	$levels		= $tax_obj->levels ?? 0;

	if($levels > 1){
		$args['depth']	= $levels - 1;

		if($action_type == 'edit'){
			$term_id		= $args['exclude_tree'];
			$term_levels	= count(get_ancestors($term_id, $taxonomy, 'taxonomy'));
			$child_levels	= $term_levels;

			$children	= get_term_children($term_id, $taxonomy);
			if($children){
				$child_levels = 0;

				foreach($children as $child){
					$new_child_levels	= count(get_ancestors($child, $taxonomy, 'taxonomy'));
					if($child_levels	< $new_child_levels){
						$child_levels	= $new_child_levels;
					}
				}
			}

			$redueced	= $child_levels - $term_levels;

			if($redueced < $args['depth']){
				$args['depth']	-= $redueced;
			}else{
				$args['parent']	= -1;
			}
		}
	}

	return $args;
}, 10, 3);

add_filter('wpjam_term_options', function($term_options, $taxonomy){
	if($thumbnail_field	= wpjam_get_term_thumbnail_field($taxonomy)){
		$term_options['thumbnail']	= $thumbnail_field;
	}

	return $term_options;
},99,2);

add_filter($taxonomy.'_row_actions', function($row_actions){
	unset($row_actions['set_thumbnail']);
	return $row_actions;
});

add_action('wpjam_'.$taxonomy.'_terms_actions', function($actions, $taxonomy){
	if($thumbnail_field	= wpjam_get_term_thumbnail_field($taxonomy)){
		$actions['set_thumbnail']	= ['title'=>'设置',	'page_title'=>'设置缩略图',	'tb_width'=>'500',	'tb_height'=>'400'];
	}

	return $actions;
}, 10, 2);

add_filter('wpjam_'.$taxonomy.'_terms_fields', function($fields, $action_key, $term_id, $taxonomy){
	if($action_key == '' || $action_key == 'add' || $action_key == 'edit'){
		$term_fields	= wpjam_get_term_options($taxonomy) ?: [];
		
		if($term_fields){
			if($action_key == ''){
				$term_fields	= array_filter($term_fields, function($field){ return !empty($field['show_admin_column']); });
			}

			$fields	= array_merge($fields, $term_fields);
		}
	}elseif($action_key == 'set_thumbnail'){
		if($thumbnail_field	= wpjam_get_term_thumbnail_field($taxonomy)){
			$thumbnail_field['value']	= get_term_meta($term_id, 'thumbnail', true);

			return [
				'name'		=> ['title'=>'名称',	'type'=>'view',	'value'=>get_term($term_id)->name],
				'thumbnail'	=> $thumbnail_field
			];
		}
	}

	return $fields;
}, 10, 4);

add_filter('wpjam_'.$taxonomy.'_terms_list_action', function($result, $list_action, $term_id, $data){
	if($list_action == 'set_thumbnail'){
		$thumbnail	= $data['thumbnail'] ?? '';
		if($thumbnail){
			return update_term_meta($term_id, 'thumbnail', $thumbnail);
		}else{
			return delete_term_meta($term_id, 'thumbnail');
		}
	}

	return $result;
}, 10, 4);

function wpjam_get_term_thumbnail_field($taxonomy){
	$field	= [];

	$term_thumbnail_taxonomies	= wpjam_cdn_get_setting('term_thumbnail_taxonomies');

	if($term_thumbnail_taxonomies && in_array($taxonomy, $term_thumbnail_taxonomies)){
		$field	= ['title'=>'缩略图', 'show_admin_column'=>true, 'column_callback'=>'wpjam_get_admin_term_list_thumbnail'];

		if(wpjam_cdn_get_setting('term_thumbnail_type') == 'img'){
			$field['type']		= 'img';
			$field['item_type']	= 'url';

			$width	= wpjam_cdn_get_setting('term_thumbnail_width') ?: 200;
			$height	= wpjam_cdn_get_setting('term_thumbnail_height') ?: 200;

			if($width || $height){
				$field['size']			= $width.'x'.$height;
				$field['description']	= '尺寸：'.$width.'x'.$height;
			}
		}else{
			$field['type']	= 'image';
		}
	}

	return $field;	
}

function wpjam_get_admin_term_list_thumbnail($term_id){
	$term_thumbnail	= wpjam_get_term_thumbnail($term_id, [50,50]);

	$taxonomy	= get_term($term_id)->taxonomy;
	$capability	= get_taxonomy($taxonomy)->cap->edit_terms;

	if(!current_user_can($capability)){
		return $term_thumbnail;
	}

	return wpjam_get_list_table_row_action('set_thumbnail',[
		'id'	=> $term_id,
		'title'	=> $term_thumbnail ?: '设置缩略图',
	]);
}