<?php
add_action('wpjam_'.$post_type.'_posts_actions', function($actions){
	return $actions + ['quick_duplicate'=>['title'=>'快速复制',	'response'=>'add',	'direct'=>true]];
},1);

add_filter('wpjam_'.$post_type.'_posts_list_action', function($result, $list_action, $post_id, $data){
	if($list_action == 'quick_duplicate'){

		$post_arr	= get_post($post_id, ARRAY_A);

		unset($post_arr['ID']);
		unset($post_arr['post_date_gmt']);
		unset($post_arr['post_modified_gmt']);
		unset($post_arr['post_name']);

		$post_arr['post_status']	= 'draft';
		$post_arr['post_author']	= get_current_user_id();
		$post_arr['post_date_gmt']	= $post_arr['post_modified_gmt']	= date('Y-m-d H:i:s', time());
		$post_arr['post_date']		= $post_arr['post_modified']		= get_date_from_gmt($post_arr['post_date_gmt']);

		$tax_input	= [];

		$taxonomies	= get_object_taxonomies($post_arr['post_type']);
		foreach($taxonomies as $taxonomy){
			$tax_input[$taxonomy]	= wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
		}

		$post_arr['tax_input']	= $tax_input;

		$new_post_id	= wp_insert_post($post_arr, true);

		if(is_wp_error($new_post_id)){
			return $new_post_id;
		}

		$meta_keys	= get_post_custom_keys($post_id);

		foreach ($meta_keys as $meta_key) {
			if(in_array($meta_key, ['views', 'likes', 'favs']) || is_protected_meta($meta_key, 'post')){
				continue;
			}

			$meta_values	= get_post_meta($post_id, $meta_key);
			foreach ($meta_values as $meta_value){
				add_post_meta($new_post_id, $meta_key, $meta_value, false);
			}
		}

		return $new_post_id;
	}

	return $result;
}, 10, 4);
